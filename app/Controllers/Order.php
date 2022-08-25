<?php

namespace App\Controllers;

use \CodeIgniter\API\ResponseTrait;

class Order extends \App\Controllers\BaseController {

    use ResponseTrait;

    public function itemGet($order_id=null) {
        if( !$order_id ){
            $order_id = $this->request->getVar('order_id');
        }
        $OrderModel = model('OrderModel');
        $result = $OrderModel->itemGet($order_id);
        if ($result === 'forbidden') {
            return $this->failForbidden($result);
        }
        if ($result === 'notfound') {
            return $this->failNotFound($result);
        }
        return $this->respond($result);
    }

    public function itemDetailsPrepaymentGet(){
        $order_id = $this->request->getVar('order_id');
        $TransactionModel = model('TransactionModel');
        $filter=(object)[
            'trans_tags'=>'#orderPaymentFixation',
            'trans_holder'=>'order',
            'trans_holder_id'=>$order_id
        ];
        $trans=$TransactionModel->itemFind($filter);
        if(!$trans){
            return $this->failNotFound('notfound');
        }
        return $this->respond([
            'trans_id'=>$trans->trans_id,
            'trans_amount'=>$trans->trans_amount
        ]);
    }

    public function itemCreate($order_store_id=null) {
        $order_store_id = $this->request->getVar('order_store_id');
        $OrderModel = model('OrderModel');
        $result = $OrderModel->itemCreate($order_store_id);
        if ($result === 'forbidden') {
            return $this->failForbidden($result);
        }
        if ($result === 'noorder') {
            return $this->fail($result);
        }
        if ($OrderModel->errors()) {
            return $this->failValidationErrors($OrderModel->errors());
        }
        return $this->respond($result);
    }

    public function itemSync() {
        $data = $this->request->getJSON();
        if(!$data){
            return $this->fail('malformed_request');
        }
        if( session()->get('user_id')<=0 && session()->get('user_id')!=-100 ){//system user
            return $this->failUnauthorized('unauthorized');
        }
        $OrderModel = model('OrderModel');
        $order_id_exists=false;
        if( ($data->order_id??-1)>0 ){
            $order_id_exists=$OrderModel->where($data->order_id)->get()->getRow('order_id');
        }
        $OrderModel->transStart();
        if( !$order_id_exists ){
            if( !isset($data->order_store_id) ){
                $OrderModel->transRollback();
                return $this->fail('nostoreid');
            }
            $result=$OrderModel->itemCreate($data->order_store_id);
            if ($result === 'forbidden') {
                $OrderModel->transRollback();
                return $this->failForbidden($result);
            }
            if (!is_numeric($result)) {
                $OrderModel->transRollback();
                return $this->fail($result);
            }
            $data->order_id=$result;
        }
        $result = $OrderModel->itemUpdate($data);
        if ($result === 'forbidden') {
            $OrderModel->transRollback();
            return $this->failForbidden($result);
        }
        if ($result === 'validation_error') {
            $OrderModel->transRollback();
            return $this->fail($result);
        }
        if ($OrderModel->errors()) {
            $OrderModel->transRollback();
            return $this->failValidationErrors($OrderModel->errors());
        }
        $OrderModel->transComplete();
        return $this->itemGet($data->order_id);
    }


    public function itemUpdate() {
        $data = $this->request->getJSON();
        if(!$data){
            return $this->fail('malformed_request');
        }
        $OrderModel = model('OrderModel');
        $result = $OrderModel->itemUpdate($data);
        if ($result === 'forbidden') {
            return $this->failForbidden($result);
        }
        if ($OrderModel->errors()) {
            return $this->failValidationErrors($OrderModel->errors());
        }
        return $this->respondUpdated($result);
    }

    public function itemStageCreate() {
        $order_id = $this->request->getVar('order_id');
        $new_stage = $this->request->getVar('new_stage');
        return $this->itemStage($order_id, $new_stage);
    }

    private function itemStage($order_id, $stage) {
        $OrderModel = model('OrderModel');
        $result = $OrderModel->itemStageCreate($order_id, $stage);
        if ($result === 'ok') {
            return $this->respondUpdated($result);
        }
        if ($result === 'forbidden') {
            return $this->failForbidden($result);
        }
        return $this->fail($result);
    }

    public function itemDelete() {
        $order_id = $this->request->getVar('order_id');
        return $this->itemStage($order_id, 'customer_deleted');
    }

    public function itemUnDelete() {
        $order_id = $this->request->getVar('order_id');
        return $this->itemStage($order_id, 'customer_cart');
    }

    public function itemDisable() {
        return $this->failNotFound();
    }

    public function itemPurge(){
        $order_id=$this->request->getVar('order_id');
        
        $OrderModel=model('OrderModel');
        $result=$OrderModel->itemPurge($order_id);        
        if( $result==='ok' ){
            return $this->respondDeleted($result);
        }
        if( $result==='forbidden' ){
            return $this->failForbidden($result);
        }
        return $this->fail($result);   
    }

    public function listGet() {
        $filter=[
            'name_query'=>$this->request->getVar('name_query'),
            'name_query_fields'=>$this->request->getVar('name_query_fields'),
            'is_deleted'=>$this->request->getVar('is_deleted'),
            'is_active'=>$this->request->getVar('is_active'),
            'limit'=>$this->request->getVar('limit'),
            'user_role'=>$this->request->getVar('user_role'),
            'order_store_id'=>$this->request->getVar('order_store_id'),
            'order_group_type'=>$this->request->getVar('order_group_type'),
            'date_start'=>$this->request->getVar('date_start'),
            'date_finish'=>$this->request->getVar('date_finish')
        ];
        $OrderModel=model('OrderModel');
        $order_list=$OrderModel->listGet($filter);
        return $this->respond($order_list);
    }

    public function listCountGet(){
        $OrderModel=model('OrderModel');
        $count=$OrderModel->listCountGet();
        return $this->respond($count);
    }
    

    // public function listCartGet(){
    //     $OrderModel=model('OrderModel');
    //     $order_list=$OrderModel->listCartGet();
    //     return $this->respond($order_list);
    // }

    public function listStageGet() {
        $OrderGroupModel = model('OrderGroupModel');
        $result = $OrderGroupModel->listGet();
        return $this->respond($result);
    }

    public function listCreate() {
        
    }

    public function listUpdate() {
        return false;
    }

    public function listDelete() {
        return false;
    }

    /////////////////////////////////////////////////////
    //IMAGE HANDLING SECTION
    /////////////////////////////////////////////////////
    public function fileUpload() {
        $image_holder_id = $this->request->getVar('image_holder_id');
        if ( !(int) $image_holder_id ) {
            return $this->fail('no_holder_id');
        }
        $items = $this->request->getFiles();
        if (!$items) {
            return $this->failResourceGone('no_files_uploaded');
        }
        foreach ($items['files'] as $file) {
            $type = $file->getClientMimeType();
            if (!str_contains($type, 'image')) {
                continue;
            }
            if ($file->isValid() && !$file->hasMoved()) {
                $result = $this->fileSaveImage($image_holder_id, $file);
                if ($result !== true) {
                    return $result;
                }
            }
        }
        return $this->respondCreated('ok');
    }

    private function fileSaveImage($image_holder_id, $file) {
        $image_data = [
            'image_holder' => 'order',
            'image_holder_id' => $image_holder_id
        ];
        $OrderModel = model('OrderModel');
        $image_hash = $OrderModel->imageCreate($image_data);
        if (!$image_hash) {
            return $this->failForbidden('forbidden');
        }
        if ($image_hash === 'limit_exeeded') {
            return $this->fail('limit_exeeded');
        }
        $file->move(WRITEPATH . 'images/', $image_hash . '.webp');

        return \Config\Services::image()
                        ->withFile(WRITEPATH . 'images/' . $image_hash . '.webp')
                        ->resize(1024, 1024, true, 'height')
                        ->convert(IMAGETYPE_WEBP)
                        ->save();
    }

    public function imageDelete() {
        $image_id = $this->request->getVar('image_id');

        $OrderModel = model('OrderModel');
        $result = $OrderModel->imageDelete($image_id);
        if ($result === 'ok') {
            return $this->respondDeleted($result);
        }
        return $this->fail($result);
    }

    public function imageOrder(){
        $image_id=$this->request->getVar('image_id');
        $dir=$this->request->getVar('dir');
        
        $OrderModel=model('OrderModel');
        $result=$OrderModel->imageOrder( $image_id, $dir );
        if( $result==='ok' ){
            return $this->respondUpdated($result);
        }
        return $this->fail($result);
    }


}
