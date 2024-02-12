<?php
namespace App\Controllers;
use \CodeIgniter\API\ResponseTrait;

if(getenv('CI_ENVIRONMENT')!=='development'){
    die('!!!');
}


class TestController extends \App\Controllers\BaseController{
    use ResponseTrait;
    
    public function push(){
        $FirePush=new \App\Libraries\FirePushKreait;
        $result=$FirePush->sendPush((object)[
            'token'=>[
                //'ebzdBTFTu-Ob2sByx05HK_:APA91bHbf1fGGk7ogzaxPHOdHr1HVRyHmsD3PbBFvqJkXZZP38deNZe1GodM3ft1XguNIL2Oe3CQ77N1AdccKYxFvuvDUYZIAL8K1MwHusudB03RVZBhb9Z9QQwedVZNSHgP1ecdrgQo',//chrome
                //'fXF3H6KOobooZA-1h_Ttc0:APA91bGeqZyCTWxqV42R5-ez-c5GfSC7WcyXUpmCc6g-WBuAGPrZNtGD8edMNEwXpzGfn-kvwZVn_xfJVEquRJ0iUYMheJ_NJlW9YohUFjpvNmuSYwasOV55xpow4esoMH8aXMCc3qsZ',//ff
                //'c8aL3e93u7QEvPUU8tfZfW:APA91bFK-oKAhRY3j0JNId8Kwg8bWywW0GAstUGZcLOC2Nd0E63RmVoVWfgSx2Y2e-qm7FtpQUslFBsqDI92Ib3Fjyp_QYh7H0FZy6DCZDOK26yp0R5RCJGswlpGkspbJXhuRrm0zUmv',//edge
                //'eXo5AfJf6EAWit3PHXlcVa:APA91bE7kV-HOEAm6JR1kzHClF7wJwHuPYAAqlAPsprL41hQBdRJyU14ItERjy2m5alcwj-2BKjx29rn1pBXuw5aThUEB8h0M4VAS4Qd8gd_KCz52lxPYzdHw2FV0SexGClekNj_rLcv',//ios
                'eaqq_3W7S46Ia-gIGnwSiG:APA91bESLQSt7a9maARXgDPXMhNHB-H4ZGiIYuYAybHqd-c2Cl0avGiJ2RT1AVwVjIYJOClOHq3X7gzJnOS4GUPJEm0HmnStT4X8amh-V357-yueqkV0_gfPwn_MqHgzQ9rfR5di92zr',//android
                //'cJNIK-OXTQm5hhpCTJhzIk:APA91bH2QlbVmgIdxHdJ7HF1wRzLs6DE8KCZGSTXhkROB-fch4K2zM1iXm3S0ywLr38P8J8RWxd8qqKDatLZ2URTUUn4u1tTceEmQEZvbZ6KIlzePdLZR2jAKnYdSuS0karyfKWAGnLR'
            ],
            'title'=>'TeSt PuSh😀😀😀😀',
            'body'=>'Test body '.date("H:i:s"),
            'data'=>[
                 'link'=>'/catalog/product-1615',
                 //'tag'=>'#orderStatus',
                 'image'=>'https://api.tezkel.com/image/get.php/fafa5407eaf897fd8b2d378e6c011f42.600.600.jpg',
                 //'icon'=>'default_notification_icon',
                 'sound'=>'long.wav',

                //  'topic'=>'pushStageChanged',
                //  'order_id'=>2457,
                //  'orderActiveCount'=>55,
                //  'stage'=>'customer_start',
            ],
        ]);

        header("Refresh:15");
        echo $result;
    }
    public function push2(){
        $Messenger=new \App\Libraries\Messenger;
        $result=$Messenger->itemSend((object)[
            'message_reciever_id'=>41,
            'message_transport'=>'push',
            'message_subject'=>'TeSt PuSh😀😀😀😀',
            'message_text'=>'Test body '.date("H:i:s"),
            'message_data'=>(object)[
                 'link'=>'https://tezkel.com/catalog/product-1615',
                 'tag'=>'#orderStatus',
                 'image'=>'https://api.tezkel.com/image/get.php/fafa5407eaf897fd8b2d378e6c011f42.600.600.jpg',
                 //'icon'=>'default_notification_icon',
                 'sound'=>'short.wav',

                  'topic'=>'pushStageChanged',
                //  'order_id'=>2457,
                  'orderActiveCount'=>55,
                  'stage'=>'customer_start',
            ],
        ]);

        header("Refresh:15");
        echo $result;
    }

    public function shiftCalc(){
        $CourierShiftModel=model('CourierShiftModel');


        $result=$CourierShiftModel->itemReportSend(457);
        return $this->respond($result);
    }

    // private $order_id=4880;
    // public function rncbLink(){
    //     $OrderModel=model('OrderModel');

    //     $order_all=$OrderModel->itemGet($this->order_id);
    //     $Acquirer=\Config\Services::acquirer();
    //     $link=$Acquirer->linkGet($order_all);
    //     header("Location: $link");
    // }

    // public function rncbStatus(){
    //     $OrderModel=model('OrderModel');

    //     $order_all=$OrderModel->itemGet($this->order_id);
    //     $Acquirer=\Config\Services::acquirer();
    //     $paymentStatus=$Acquirer->statusGet($order_all->order_id);
    //     return $this->respond($paymentStatus);
    // }


    // public function rncbDo(){
    //     $OrderModel=model('OrderModel');
    //     $order_data=$OrderModel->itemDataGet($this->order_id);

    //     $order_sum=(float)$order_data->payment_card_fixate_sum;
    //     $refund=(float)105;
    //     $confirm=$order_sum-$refund;

    //     $isFullRefund=($refund==$order_sum)?1:0;
    //     $isFullConfirm=($confirm==$order_sum)?1:0;


    //     $Acquirer=\Config\Services::acquirer();
    //     $ref=$Acquirer->refund($order_data->payment_card_fixate_id,$refund,$isFullRefund);
    //     $con=$Acquirer->confirm($order_data->payment_card_fixate_id,$order_sum-$refund);

    //     $paymentStatus=$Acquirer->statusGet($this->order_id);
    //     p([$ref,$con,$paymentStatus,]);
    // }


    // public function rncbPay(){
    //     $OrderModel=model('OrderModel');
    //     $order_all=$OrderModel->itemGet($this->order_id);

    //     $order_sum=(float)50000;$order_all->order_sum_total;
    //     $refund=(float)405;
    //     $confirm=$order_sum-$refund;

    //     $isFullRefund=($refund==$order_sum)?1:0;
    //     $isFullConfirm=($confirm==$order_sum)?1:0;

    //     $Acquirer=new \App\Libraries\AcquirerRncb();

    //     $orderData=(object)[
    //         "payment_card_fixate_id"=>null,
    //     ];
    //     $OrderModel->fieldUpdateAllow('order_data');
    //     $OrderModel->itemDataUpdate($order_all->order_id,$orderData);



    //     $auth=$Acquirer->pay($order_all);
    //     if( $auth!='ok' ){
    //         return $this->fail($auth);
    //     }
    //     $order_data=$OrderModel->itemDataGet($this->order_id);

    //     $ref=$Acquirer->refund($order_data->payment_card_fixate_id,$refund,$isFullRefund);
    //     $con=$Acquirer->confirm($order_data->payment_card_fixate_id,$order_sum-$refund);

    //     $paymentStatus=$Acquirer->statusGet($this->order_id);
    //     p([$auth,$ref,$con,$paymentStatus,]);
    // }

        public function courierTest(){
            $order_id=4943;
            $order_courier_id=12;

            $OrderGroupMemberModel=model('OrderGroupMemberModel');
            $OrderModel=model('OrderModel');
            $CourierModel=model('CourierModel');

            $OrderGroupMemberModel->leaveGroupByType($order_id,'delivery_search');

            $OrderModel->itemStageAdd($order_id,'delivery_search');

            $CourierModel->itemUpdateStatus($order_courier_id,'ready');
        }


}
