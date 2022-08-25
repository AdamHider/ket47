<?php
namespace App\Libraries;
class CashierKitOnline{
    private $url_api = "https://api.kit-invest.ru/WebService.svc/";

    public function print($order_all){
        $Check=[
            'CheckId'=>$order_all->order_id,
            'TaxSystemType'=>getenv('kitonline.TaxSystemType'),
            'CalculationType'=>'1',
            'Sum'=>$order_all->order_sum_total*100,
            'Email'=>'',
            'Phone'=>'',
            'Pay'=>[
                'CashSum'=>0,
                'EMoneySum'=>$order_all->order_sum_total*100,
            ],
            'Subjects'=>[]
        ];
        if( !$order_all?->entries ){
            return 'noentries';
        }
        $order_sum_calculated=0;
        $discount_modifier=1;
        if($order_all->order_sum_promo>0){
            $discount_modifier=(1-$order_all->order_sum_promo/$order_all->order_sum_product);
        }
        foreach($order_all->entries as $entry){
            $Check['Subjects'][]=[
                'SubjectName'=>$entry->entry_text,
                'Price'=>round($entry->entry_price*100*$discount_modifier),
                'Quantity'=>$entry->entry_quantity,
                'Tax'=>6,//without VAT,
                'GoodsAttribute'=>1,//Products
                'unitOfMeasurement'=>$entry->product_unit,
                'supplierINN'=>$order_all->store->store_tax_num,
                'supplierInfo'=>[
                    'name'=>$order_all->store->store_name,
                    'phoneNumbers'=>[$order_all->store->store_phone]
                ],
                'agentType'=>64,//АГЕНТ
                'agentInfo'=>[
                    //'paymentAgentOperation'=>getenv('kitonline.paymentAgentOperation'),
                    'paymentAgentPhoneNumbers'=>[getenv('kitonline.paymentAgentPhoneNumbers')]
                ]
            ];

            $order_sum_calculated+=round($entry->entry_price*100*$discount_modifier)*$entry->entry_quantity;
        }
        if($order_all->order_sum_delivery>0){
            $Check['Subjects'][]=[
                'SubjectName'=>'Услуга доставки заказа',
                'Price'=>$order_all->order_sum_delivery*100,
                'Quantity'=>1,
                'Tax'=>6,//without VAT,
                'GoodsAttribute'=>4,//Service
                'unitOfMeasurement'=>"шт",
            ];

            $order_sum_calculated+=round($order_all->order_sum_delivery*100);
        }
        $order_sum_error=$Check['Sum']-$order_sum_calculated;
        if( $order_sum_error!=0 ){
            $Check=$this->correcterEntryCreate($Check,$order_sum_error);
        }
        $data=[
            'Request'=>[
                'FiscalData'=>1,
                'Link'=>1,
                'QRCode'=>1,
            ],
            'Check'=>$Check
        ];
        return $this->apiExecute($order_all->order_id,'SendCheck',$data);
    }

    private function correcterEntryCreate($Check,$order_sum_error){
        if( $Check['Subjects'][0]['Quantity']==1 ){
            $Check['Subjects'][0]['Price']+=$order_sum_error;
            return $Check;
        }
        if( $Check['Subjects'][0]['Quantity']>1 ){
            $Check['Subjects'][0]['Quantity']--;
            $correcter_entry=$Check['Subjects'][0];
            $correcter_entry['Quantity']=1;
            $correcter_entry['Price']+=$order_sum_error;
            array_unshift($Check['Subjects'],$correcter_entry);
            return $Check;
        }
    }

    public function statusGet($CheckNumber){
        $data=[
            'Request'=>[
                'FiscalData'=>1,
                'Link'=>1,
                'QRCode'=>1,
            ],
            'CheckNumber'=>$CheckNumber
        ];
        return $this->apiExecute($CheckNumber,'StateCheck',$data);
    }

    public function printAndGet($order_all){
        $response=$this->print($order_all);
        if( $response->ResultCode!=0 ){
            return $response;
        }
        $atempt_count=10;
        while($atempt_count>0){
            $atempt_count--;
            sleep(2);
            $response=$this->statusGet($order_all->order_id);
            if($response->ResultCode!=0){
                continue;
            }
            if($response->CheckState->State<1000){
                continue;
            }
            return $response;
        }
    }

    private function apiExecute($CheckNumber,$method,$data){
        $url = $this->url_api.$method;
        $data['Request']['CompanyId']=getenv('kitonline.CompanyId');
        $data['Request']['UserLogin']=getenv('kitonline.UserLogin');
        $data['Request']['Sign']=md5(getenv('kitonline.CompanyId').getenv('kitonline.Password').$CheckNumber);
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/json",
                'method'  => 'POST',
                'content' => json_encode($data)
            )
        );
        $context  = stream_context_create($options);
        $response_text = file_get_contents($url, false, $context);
        $response=json_decode($response_text);
        if($response->ResultCode!=0){
            log_message('critical',"CashierKitOnline on Order #{$CheckNumber} api ResultCode is:".$response->ResultCode);
        }
        return $response;
    }
}