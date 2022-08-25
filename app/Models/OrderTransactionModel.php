<?php
namespace App\Models;

class OrderTransactionModel extends TransactionModel{
    /**
     * Sum fixation (Initial transactions)
     * Sum confirmation (Final order sum transact)
     * Sum refundment (Initially fixed amount's leftovers)
     */

    ///////////////////////////////////////////////////////////////////////
    //FIXATION INITIAL ORDER TRANSACTIONS AND ACTIONS
    ///////////////////////////////////////////////////////////////////////
    // public function orderPaymentFixate($order_basic){
    //     $OrderGroupMemberModel=model('OrderGroupMemberModel');
    //     $is_card_payment=$OrderGroupMemberModel->isMemberOf($order_basic->order_id,'customer_payed_card');
    //     $is_cash_payment=$is_card_payment?false:$OrderGroupMemberModel->isMemberOf($order_basic->order_id,'customer_payed_cash');
    //     if($is_card_payment){
    //         return $this->orderPaymentFixateCard($order_basic);
    //     }
    //     if($is_cash_payment){
    //         return $this->orderPaymentFixateCash($order_basic);
    //     }
    // }

    public function orderPaymentFixateCard($order_basic,$acquirer_data){
        $filter=(object)[
            'trans_tags'=>'#orderPaymentFixation',
            'trans_holder'=>'order',
            'trans_holder_id'=>$order_basic->order_id
        ];
        $orderPaymentFixationTrans=$this->itemFind($filter);
        if($orderPaymentFixationTrans){
            return 'ok';
        }
        // $Acquirer=\Config\Services::acquirer();
        // $acquirer_data=$Acquirer->statusGet($order_basic->order_id);
        if( !$acquirer_data ){//connection error need to repeat
            return 'connection_error';
        }
        if( $acquirer_data->status=='canceled' || $acquirer_data->status=='partly canceled' ){
            //already canceled
            return 'canceled';
        }
        if( $acquirer_data->status=='waiting' ){
            return 'waiting';
        }
        $trans=[
            'trans_amount'=>$order_basic->order_sum_total,
            'trans_data'=>json_encode($acquirer_data),
            'trans_role'=>'customer.card->money.acquirer.blocked',
            'trans_tags'=>'#orderPaymentFixation',
            'owner_id'=>$order_basic->owner_id,
            'is_disabled'=>0,
            'trans_holder'=>'order',
            'trans_holder_id'=>$order_basic->order_id
        ];
        $trans_id=$this->itemCreate($trans);
        return is_numeric($trans_id)?'ok':'error';
    }

    public function orderPaymentFixateCash($order_basic){
        return false;
    }
    ///////////////////////////////////////////////////////////////////////
    //FINALIZATION OF ORDER TRANSACTIONS AND ACTIONS 
    ///////////////////////////////////////////////////////////////////////
    public function orderFinalize($order_id){
        $UserModel=model('UserModel');
        $OrderModel=model('OrderModel');
        $UserModel->systemUserLogin();
        $order_basic=$OrderModel->itemGet($order_id,'basic');
        $finalized=$this->orderPaymentFinalize($order_basic);
        if($finalized){
            return $OrderModel->itemStageCreate($order_id,'customer_finish');
        }
        $UserModel->systemUserLogout();
        return 'failed';
    }

    public function orderPaymentFinalizeCheck($order_id){
        $this->isCustomerFullyRefunded=model('OrderGroupMemberModel')->isMemberOf($order_id,'customer_refunded');
        if($this->isCustomerFullyRefunded){
            return $this->orderPaymentFind($order_id,'#orderPaymentRefund');
        }
        return 
           $this->orderPaymentFind($order_id,'#orderPaymentConfirm')
        && $this->orderPaymentFind($order_id,'#orderPaymentRefund')
        && $this->orderPaymentFind($order_id,'#orderInvoice');
    }

    public function orderPaymentFinalize($order_basic){
        $this->isCustomerFullyRefunded=model('OrderGroupMemberModel')->isMemberOf($order_basic->order_id,'customer_refunded');
        return 
           $this->orderPaymentFinalizeConfirm($order_basic)
        && $this->orderPaymentFinalizeRefund($order_basic)
        && $this->orderPaymentFinalizeInvoice($order_basic)
        && $this->orderPaymentFinalizeSettle($order_basic);
    }

    private $orderPaymentFindCache=[];
    private function orderPaymentFind($order_id,$trans_tag){
        if( !isset($this->orderPaymentFindCache[$trans_tag]) ){
            $filter=(object)[
                'trans_holder'=>'order',
                'trans_holder_id'=>$order_id,
                'trans_tags'=>$trans_tag,
            ];
            $this->orderPaymentFindCache[$trans_tag]=$this->itemFind($filter);
        }
        return $this->orderPaymentFindCache[$trans_tag];
    }

    private function orderPaymentFinalizeConfirm($order_basic){//Claim payment for order
        if( $this->orderPaymentFind($order_basic->order_id,'#orderPaymentConfirm') ){
            return true;
        }
        if( $this->isCustomerFullyRefunded ){
            return true;
        }
        $orderPaymentFixationTrans=$this->orderPaymentFind($order_basic->order_id,'#orderPaymentFixation');
        $billNumber=$orderPaymentFixationTrans?->trans_data?->billNumber;
        if( !$orderPaymentFixationTrans || !$billNumber ){
            log_message('error',"Payment confirmation failed for order #{$order_basic->order_id}. Fixation trans is not found");
            return false;
        }
        $sumPreviuslyBlocked=$orderPaymentFixationTrans->trans_amount;
        $sumToConfirm=$order_basic->order_sum_total;
        if( $sumPreviuslyBlocked<$sumToConfirm ){
            log_message('error',"Payment confirmation failed for order #{$order_basic->order_id}. Fixation amount is smaller");
            return false;
        }
        $Acquirer=\Config\Services::acquirer();
        $acquirer_data=$Acquirer->confirm($billNumber,$order_basic->order_sum_total);
        if( !$acquirer_data ){//connection error need to repeat
            return false;
        }
        $trans=[
            'trans_amount'=>$sumToConfirm,
            'trans_data'=>json_encode($acquirer_data),
            'trans_role'=>'money.acquirer.blocked->money.acquirer.confirmed',
            'trans_tags'=>'#orderPaymentConfirm',
            'owner_id'=>$order_basic->owner_id,
            'is_disabled'=>0,
            'trans_holder'=>'order',
            'trans_holder_id'=>$order_basic->order_id
        ];
        return $this->itemCreate($trans);
    }

    private function orderPaymentFinalizeRefund($order_basic){//Made refund of excess money
        if( $this->orderPaymentFind($order_basic->order_id,'#orderPaymentRefund') ){
            return true;
        }
        $orderPaymentFixationTrans=$this->orderPaymentFind($order_basic->order_id,'#orderPaymentFixation');
        $billNumber=$orderPaymentFixationTrans?->trans_data?->billNumber;
        if( !$orderPaymentFixationTrans || !$billNumber ){
            log_message('error',"Payment Refunding failed for order #{$order_basic->order_id}. Fixation trans is not found");
            return false;
        }
        $sumPreviuslyBlocked=$orderPaymentFixationTrans->trans_amount;
        if( $this->isCustomerFullyRefunded ){
            $sumToRefund=$sumPreviuslyBlocked;
        } else {
            $sumToRefund=$sumPreviuslyBlocked-$order_basic->order_sum_total;
        }
        if( $sumToRefund<=0 ){
            return true;
        }

        $Acquirer=\Config\Services::acquirer();
        $acquirer_data=$Acquirer->refund($billNumber,$sumToRefund);
        if( !$acquirer_data ){//connection error need to repeat
            return false;
        }
        $trans=[
            'trans_amount'=>$sumToRefund,
            'trans_data'=>json_encode($acquirer_data),
            'trans_role'=>'money.acquirer.blocked->customer.card',
            'trans_tags'=>'#orderPaymentRefund',
            'owner_id'=>$order_basic->owner_id,
            'is_disabled'=>0,
            'trans_holder'=>'order',
            'trans_holder_id'=>$order_basic->order_id
        ];
        return $this->itemCreate($trans);    
    }

    private function orderPaymentFinalizeInvoice($order_basic){//Create tax invoice
        if( $this->isCustomerFullyRefunded ){
            return true;
        }
        if( $this->orderPaymentFind($order_basic->order_id,'#orderInvoice') ){
            return true;
        }
        $order_all=model('OrderModel')->itemGet($order_basic->order_id);
        $sumInInvoice=$order_all->order_sum_product;
        $Cashier=\Config\Services::cashier();
        $cashier_data=$Cashier->printAndGet($order_all);

        if( !$cashier_data ){//connection error need to repeat
            return false;
        }
        $trans=[
            'trans_amount'=>$sumInInvoice,
            'trans_data'=>json_encode($cashier_data),
            'trans_role'=>'supplier->customer',
            'trans_tags'=>'#orderInvoice',
            'owner_id'=>$order_all->owner_id,
            'owner_ally_ids'=>$order_all->owner_ally_ids,
            'is_disabled'=>0,
            'trans_holder'=>'order',
            'trans_holder_id'=>$order_all->order_id
        ];
        return $this->itemCreate($trans);
    }

    private function orderPaymentFinalizeSettle($order_basic){//Calculate profits, interests etc
        return 
           $this->orderPaymentFinalizeSettleCommission($order_basic)
        && $this->orderPaymentFinalizeSettleDelivery($order_basic);
    }

    private function orderPaymentFinalizeSettleCommission($order_basic){
        if( $this->orderPaymentFind($order_basic->order_id,'#orderCommission') ){
            return true;
        }
        $store=model('StoreModel')->itemGet($order_basic->order_store_id,'basic');
        $storeCommissionPercent=$store->store_commission??25;
        $sumCommission=$order_basic->order_sum_product*($storeCommissionPercent/100);
        $trans=[
            'trans_amount'=>$sumCommission,
            'trans_role'=>'capital.profit->supplier',
            'trans_tags'=>'#orderCommission',
            'owner_id'=>0,//customer should not see
            'owner_ally_ids'=>$store->owner_id.','.$store->owner_ally_ids,
            'is_disabled'=>0,
            'trans_holder'=>'order',
            'trans_holder_id'=>$order_basic->order_id
        ];
        return $this->itemCreate($trans);
    }
    private function orderPaymentFinalizeSettleDelivery($order_basic){
        if( $this->orderPaymentFind($order_basic->order_id,'#orderDelivery') ){
            return true;
        }        
        $courier=model('CourierModel')->itemGet($order_basic->order_courier_id,'basic');
        if( !is_object($courier) ){
            return true;
        }
        $deliveryFixedSum=50;
        $deliveryBonusPercent=10;
        $sumDelivery=$deliveryFixedSum+$order_basic->order_sum_product*($deliveryBonusPercent/100);
        $trans=[
            'trans_amount'=>$sumDelivery,
            'trans_role'=>'capital.profit->supplier',
            'trans_tags'=>'#orderDelivery',
            'owner_id'=>$courier->owner_id,
            'is_disabled'=>0,
            'trans_holder'=>'order',
            'trans_holder_id'=>$order_basic->order_id
        ];
        return $this->itemCreate($trans);
    }
}