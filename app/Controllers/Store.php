<?php

namespace App\Controllers;
use \CodeIgniter\API\ResponseTrait;

class Store extends \App\Controllers\BaseController{
    use ResponseTrait;
    
    public function listGet(){
        $filter=[
            'name_query'=>$this->request->getVar('name_query'),
            'name_query_fields'=>$this->request->getVar('name_query_fields'),
            'is_disabled'=>$this->request->getVar('is_disabled'),
            'is_deleted'=>$this->request->getVar('is_deleted'),
            'is_active'=>$this->request->getVar('is_active'),
            'limit'=>$this->request->getVar('limit'),
            'owner_id'=>$this->request->getVar('owner_id'),
        ];
        $StoreModel=model('StoreModel');
        $store_list=$StoreModel->listGet($filter);
        if( $StoreModel->errors() ){
            return $this->failValidationErrors(json_encode($StoreModel->errors()));
        }
        return $this->respond($store_list);
    }
    
    public function itemGet(){
        $store_id=$this->request->getVar('store_id');
        $StoreModel=model('StoreModel');
        $result=$StoreModel->itemGet($store_id);
        if( $result==='forbidden' ){
            return $this->failForbidden($result);
        }
        if( $result==='notfound' ){
            return $this->failNotFound($result);
        }
        return $this->respond($result);
    }
    
    
    public function itemCreate(){
        $name=$this->request->getVar('name');
        $StoreModel=model('StoreModel');
        $result=$StoreModel->itemCreate($name);
        if( $result==='forbidden' ){
            return $this->failForbidden($result);
        }
        if( $result==='limit_exeeded' ){
            return $this->failResourceExists($result);
        }
        if( $StoreModel->errors() ){
            return $this->failValidationErrors(json_encode($StoreModel->errors()));
        }
        return $this->respond($result);
    }
    
    public function itemUpdate(){
        $data= $this->request->getJSON();
        $StoreModel=model('StoreModel');
        $result=$StoreModel->itemUpdate($data);
        if( $result==='forbidden' ){
            return $this->failForbidden($result);
        }
        if( $StoreModel->errors() ){
            return $this->failValidationErrors(json_encode($StoreModel->errors()));
        }
        return $this->respondUpdated($result);
    }
    
    public function itemUpdateGroup(){
        $store_id=$this->request->getVar('store_id');
        $group_id=$this->request->getVar('group_id');
        $is_joined=$this->request->getVar('is_joined');
        
        $StoreModel=model('StoreModel');
        $result=$StoreModel->itemUpdateGroup($store_id,$group_id,$is_joined);
        if( $result==='ok' ){
            return $this->respondUpdated($result);
        }
        if( $result==='forbidden' ){
            return $this->failForbidden($result);
        }
        return $this->fail($result);
    }
    
    public function itemDelete(){
        $store_id=$this->request->getVar('store_id');
        
        $StoreModel=model('StoreModel');
        $result=$StoreModel->itemDelete($store_id);        
        if( $result==='ok' ){
            return $this->respondDeleted($result);
        }
        if( $result==='forbidden' ){
            return $this->failForbidden($result);
        }
        return $this->fail($result);   
    }
    
    public function itemUnDelete(){
        $store_id=$this->request->getVar('store_id');
        
        $StoreModel=model('StoreModel');
        $result=$StoreModel->itemUnDelete($store_id);        
        if( $result==='ok' ){
            return $this->respondUpdated($result);
        }
        if( $result==='forbidden' ){
            return $this->failForbidden($result);
        }
        return $this->fail($result);   
    }
    
    public function itemDisable(){
        $store_id=$this->request->getVar('store_id');
        $is_disabled=$this->request->getVar('is_disabled');
        
        $StoreModel=model('StoreModel');
        $result=$StoreModel->itemDisable($store_id,$is_disabled);
        if( $result==='ok' ){
            return $this->respondUpdated($result);
        }
        if( $result==='forbidden' ){
            return $this->failForbidden($result);
        }
        return $this->fail($result);
    }
    
    
    public function fieldApprove(){
        $store_id=$this->request->getVar('store_id');
        $field_name=$this->request->getVar('field_name');
        
        $StoreModel=model('StoreModel');
        $result=$StoreModel->fieldApprove( $store_id, $field_name );
        if( $result==='forbidden' ){
            return $this->failForbidden($result);
        }
        if( $StoreModel->errors() ){
            return $this->failValidationError(json_encode($StoreModel->errors()));
        }
        return $this->respondUpdated($result);
    }
    
    /////////////////////////////////////////////////////
    //IMAGE HANDLING SECTION
    /////////////////////////////////////////////////////
    public function fileUpload(){
        $image_holder_id=$this->request->getVar('image_holder_id');
        $items = $this->request->getFiles();
        if(!$items){
            return $this->failResourceGone('no_files_uploaded');
        }
        foreach($items['files'] as $file){
            $type = $file->getClientMimeType();
            if(!str_contains($type, 'image')){
                continue;
            }
            if ($file->isValid() && ! $file->hasMoved()) {
                $result=$this->fileSaveImage($image_holder_id,$file);
                if( $result!==true ){
                    return $result;
                }
            }
        }
        return $this->respondCreated('ok');
    }
    
    private function fileSaveImage( $image_holder_id, $file ){
        $image_data=[
            'image_holder'=>'store',
            'image_holder_id'=>$image_holder_id
        ];
        $StoreModel=model('StoreModel');
        $image_hash=$StoreModel->imageCreate($image_data);
        if( !$image_hash ){
            return $this->failForbidden('forbidden');
        }
        if( $image_hash === 'limit_exeeded' ){
            return $this->fail('limit_exeeded');
        }
        $file->move(WRITEPATH.'images/', $image_hash.'.webp');
        
        return \Config\Services::image()
        ->withFile(WRITEPATH.'images/'.$image_hash.'.webp')
        ->resize(1024, 1024, true, 'height')
        ->convert(IMAGETYPE_WEBP)
        ->save();
    }
    
    public function imageDisable(){
        $image_id=$this->request->getVar('image_id');
        $is_disabled=$this->request->getVar('is_disabled');
        
        $StoreModel=model('StoreModel');
        $result=$StoreModel->imageDisable( $image_id, $is_disabled );
        if( $result==='ok' ){
            return $this->respondUpdated($result);
        }
        return $this->fail($result);
    }
    
    public function imageDelete(){
        $image_id=$this->request->getVar('image_id');
        
        $StoreModel=model('StoreModel');
        $result=$StoreModel->imageDelete( $image_id );
        if( $result==='ok' ){
            return $this->respondDeleted($result);
        }
        return $this->fail($result);
    }
    
    public function imageOrder(){
        $image_id=$this->request->getVar('image_id');
        $dir=$this->request->getVar('dir');
        
        $StoreModel=model('StoreModel');
        $result=$StoreModel->imageOrder( $image_id, $dir );
        if( $result==='ok' ){
            return $this->respondUpdated($result);
        }
        return $this->fail($result);
    }
}
