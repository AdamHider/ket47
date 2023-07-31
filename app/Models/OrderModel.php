<?php
namespace App\Models;
use CodeIgniter\Model;

class OrderModel extends Model{
    
    use PermissionTrait;
    use FilterTrait;
    use OrderStageTrait;
    
    protected $table      = 'order_list';
    protected $primaryKey = 'order_id';
    protected $allowedFields = [
        'order_store_id',
        'order_store_admins',
        'order_courier_id',
        'order_courier_admins',
        'order_start_location_id',
        'order_finish_location_id',
        'order_sum_product',
        'order_sum_delivery',
        'order_sum_tax',
        'order_sum_total',//is this good idea???
        'order_description',
        'order_objection',
        'order_stock_status',
        'updated_by',
        'deleted_at'
    ];
    protected $useSoftDeletes = true;
    protected $order_tariff=null;

    public function fieldUpdateAllow($field){
        $this->allowedFields[]=$field;
    }
    
    private function itemUserRoleCalc(){
        $user_id=session()->get('user_id')??-1;
        if( sudo() ){
            $this->select("'admin' user_role");
        }
        else {
            $this->select("
                IF(order_list.owner_id=$user_id,'customer',
                IF(COALESCE(FIND_IN_SET('$user_id',order_list.order_store_admins),0),'supplier',
                IF(COALESCE(FIND_IN_SET('$user_id',order_list.order_courier_admins),0),'delivery',
                'other'))) user_role
                ");
        }
    }
    
    public function itemCacheClear(){
        $this->itemCache=[];
        $this->resetQuery();
    }
    
    public $checkPermissionForItemGet=true;
    private $itemCache=[];
    public function itemGet( $order_id, $mode='all' ){
        if( $this->itemCache[$mode.$order_id]??0 ){
            return $this->itemCache[$mode.$order_id];
        }
        $this->permitWhere('r');
        $this->select("{$this->table}.*,group_name stage_current_name,group_type stage_current");
        $this->where('order_id',$order_id);
        $this->join('order_group_list','order_group_id=group_id','left');
        $this->itemUserRoleCalc();
        $order = $this->get()->getRow();
        if( !$order ){
            return 'notfound';
        }
        $this->order_data=json_decode($order->order_data);
        unset($order->order_data);
        $this->itemInfoInclude($order);
        if($mode=='basic'){
            $this->itemCache[$mode.$order_id]=$order;
            return $order;
        }
        
        $OrderGroupMemberModel=model('OrderGroupMemberModel');
        $ImageModel=model('ImageModel');
        $EntryModel=model('EntryModel');
        $StoreModel=model('StoreModel');
        $UserModel=model('UserModel');

        // $CourierModel=model('CourierModel'); I THINK IT IS BETTER IDEA TO WRITE THIS TO ORDER DATA
        // $CourierModel->join('image_list',"image_holder='courier' AND image_holder_id=courier_id AND is_main=1",'left');
        // $CourierModel->select('courier_id,courier_name,image_hash');

        $OrderGroupMemberModel->orderBy('order_group_member_list.created_at DESC,link_id DESC');
        $StoreModel->select('store_id,store_name,store_phone,store_minimal_order,store_tax_num,image_hash');
        $StoreModel->join('image_list','image_holder="store_avatar" AND image_holder_id=store_id','left');
        $UserModel->select('user_id,user_name,user_phone,user_email');
        $order->stage_next= $this->itemStageNextGet($order->stage_current,$order->user_role);
        $order->stages=     $OrderGroupMemberModel->memberOfGroupsListGet($order->order_id);
        $order->images=     $ImageModel->listGet(['image_holder'=>'order','image_holder_id'=>$order->order_id,'is_active'=>1,'is_disabled'=>1,'is_deleted'=>1]);
        $order->entries=    $EntryModel->listGet($order_id);
        
        $order->store=      $StoreModel->where('store_id',$order->order_store_id)->get()->getRow();


        /**
         * Temporary fix to comply with old api
         */
        // $dayHour=date('H');
        // $deliveryByCourierMinimal=($dayHour>='20' || $dayHour<'10')?1000:200;
        // $order->store->store_minimal_order=max($order->store->store_minimal_order,$deliveryByCourierMinimal);

        $order->customer=   $UserModel->where('user_id',$order->owner_id)->get()->getRow();//$UserModel->itemGet($order->owner_id,'basic');permission issue for other parties
        //$order->courier=    $CourierModel->where('courier_id',$order->order_courier_id)->get()->getRow();
        $order->is_writable=$this->permit($order_id,'w');
        
        if( sudo() ){
            foreach($order->stages as $stage){
                $UserModel->select('user_id,user_name,user_phone');
                $stage->created_user=$UserModel->itemGet($stage->created_by,'basic');
            }
        }
        $this->itemCache[$mode.$order_id]=$order;
        return $order;
    }

    private function itemInfoInclude(&$order){
        if( $order->user_role??null && $this->order_data ){
            if( $order->user_role=='customer' && ($this->order_data->info_for_customer??null) ){
                $order->info=json_decode($this->order_data->info_for_customer);
            } else
            if( $order->user_role=='supplier' && ($this->order_data->info_for_supplier??null) ){
                $order->info=json_decode($this->order_data->info_for_supplier);
            } else
            if( $order->user_role=='delivery' && ($this->order_data->info_for_courier??null) ){
                $order->info=json_decode($this->order_data->info_for_courier);
            } else 
            if( $order->user_role=='admin' ){
                $order->info=(object)array_merge(
                    json_decode($this->order_data->info_for_customer??'',true)??[],
                    json_decode($this->order_data->info_for_supplier??'',true)??[],
                    json_decode($this->order_data->info_for_courier??'',true)??[]
                );
            }
        }
    }

    private $order_data;
    public function itemDataGet( int $order_id, bool $use_cache=true ){
        if( !$this->order_data || !$use_cache ){
            $this->permitWhere('r');
            $this->where('order_id',$order_id);
            $this->select('order_data');
            $this->order_data=json_decode($this->get()->getRow('order_data'));
        }
        return $this->order_data;
    }

    public function itemCreate( int $store_id ){
        if( !$this->permit(null,'w') ){
            return 'forbidden';
        }
        $StoreModel=model('StoreModel');
        $store=$StoreModel->itemGet($store_id,'basic');
        if( !$store ){
            return 'nostore';
        }
        $this->allowedFields[]='owner_id';
        $store_owners_all=ownersAll($store);
        $order_sum_delivery=$StoreModel->tariffRuleDeliveryCostGet( $store_id );//Possible delivery cost
        $user_id=session()->get('user_id');
        $new_order=[
            'owner_id'=>$user_id,
            'order_store_id'=>$store_id,
            'order_store_admins'=>$store_owners_all,
            'order_sum_delivery'=>$order_sum_delivery??0,//IF null then there is no delivery
            'order_data'=>'{}',
        ];
        $this->allowedFields[]='order_data';
        $order_id=$this->insert($new_order,true);
        /**
         * not updating owners so store will not see order at this stage 
         */
        //$this->itemUpdateOwners($order_id);
        $this->itemStageCreate( $order_id, 'customer_cart' );
        return $order_id;
    }
    
    public function itemUpdate( $order ){
        if( !$this->permit($order->order_id,'w') ){
            return 'forbidden';
        }
        if( $this->in_object($order,['entries']) ){
            $EntryModel=model('EntryModel');
            $result=$EntryModel->listUpdate($order->order_id,$order->entries);
            if( $result!='ok' ){
                return $result;
            }
            $order->order_sum_product=$EntryModel->listSumGet( $order->order_id );
        }
        $order->updated_by=session()->get('user_id');
        $this->update($order->order_id,$order);
        $update_result=$this->db->affectedRows()>0?'ok':'idle';
        if( $this->in_object($order,['owner_id','owner_ally_ids','order_store_id','order_courier_id']) ){
            $this->itemUpdateOwners($order->order_id);
        }
        return $update_result;
    }

    public function itemDataUpdate( int $order_id, object $data_update ){
        $permit_where=$this->permitWhereGet('w','item');
        $path_value='';
        foreach($data_update as $path=>$value){
            $path_value.=','.$this->db->escape("$.$path").','.$this->db->escape($value);
        }
        $this->order_data=null;//cache is unvalidated
        $sql="UPDATE order_list SET order_data=JSON_SET(`order_data`{$path_value}) WHERE order_id=$order_id AND $permit_where";
        $this->query($sql);
        return $this->db->affectedRows()?'ok':'idle';
    }

    public function itemDataDelete( int $order_id ){
        $this->permitWhere('w');
        $this->allowedFields[]='order_data';
        $this->update($order_id,['order_data'=>'{}']);
        return $this->db->affectedRows()?'ok':'idle';
    }

    public function deliverySumUpdate( int $order_id ){
        $sql="
            UPDATE
                order_list
            SET
                `order_sum_delivery` =
                IF(`order_data`->'$.delivery_by_store'=1,
                    + COALESCE(CAST(`order_data`->'$.delivery_by_store_cost' AS DECIMAL(10,2)),0)
                    ,
                    `order_sum_product` 
                    * COALESCE(CAST(`order_data`->'$.delivery_fee' AS DECIMAL(10,2)),0) 
                    + COALESCE(CAST(`order_data`->'$.delivery_cost' AS DECIMAL(10,2)),0)
                )
            WHERE
                order_id=$order_id";
        $this->query($sql);
        return $this->db->affectedRows()?'ok':'idle';
    }


    private function in_object(object $obj,array $props){
        foreach($props as $prop){
            if( property_exists($obj,$prop) ){
                return true;
            }
        }
        return false;
    }

    public function itemUpdateOwners( $order_id ){
        $this->select("(SELECT CONCAT(owner_id,',',owner_ally_ids) FROM store_list WHERE order_store_id=store_id) store_owners");
        $this->select("(SELECT CONCAT(owner_id,',',owner_ally_ids) FROM courier_list WHERE order_courier_id=courier_id) courier_owners");
        //$this->select("owner_id");,$all_owners->owner_id
        $all_owners=$this->getWhere(['order_id'=>$order_id])->getRow();
        $owners=array_map('trim',array_unique(explode(',',"0,$all_owners->store_owners,$all_owners->courier_owners"),SORT_NUMERIC));
        array_shift($owners);
        $owner_list=implode(',',$owners);

        $sql="
            UPDATE
                order_list ol
                    LEFT JOIN
                order_entry_list el USING(order_id)
            SET
                ol.owner_ally_ids='$owner_list',
                el.owner_ally_ids='$owner_list'
            WHERE
                ol.order_id='$order_id'";
        $this->query($sql);
    }
    
    public function itemPurge( $order_id ){
        $this->itemDelete($order_id);
        
        $EntryModel=model('EntryModel');
        $EntryModel->where(['order_id',$order_id])->delete(null,true);
        $this->delete($order_id,true);
        return $this->db->affectedRows()?'ok':'idle';
    }
    
    public function itemDelete( $order_id ){
        if( !$this->permit($order_id,'w') ){
            return 'forbidden';
        }
        $EntryModel=model('EntryModel');
        $EntryModel->listDeleteChildren( $order_id );
        
        $ImageModel=model('ImageModel');
        $ImageModel->listDelete('order', [$order_id]);
        
        $TransactionModel=model('TransactionModel');
        $TransactionModel->listDeleteChildren('order', $order_id);
        
        $OrderGroupMemberModel=model('OrderGroupMemberModel');
        $OrderGroupMemberModel->where('member_id',$order_id)->delete();
        
        $this->delete(['order_id'=>$order_id]);
        return $this->db->affectedRows()?'ok':'idle';
    }
    
    public function itemUnDelete( $order_id ){
        if( !$this->permit($order_id,'w') ){
            return 'forbidden';
        }//group member list
        $EntryModel=model('EntryModel');
        $EntryModel->listUnDeleteChildren( $order_id );
        
        $ImageModel=model('ImageModel');
        $ImageModel->listUnDelete('order', [$order_id]);
        
        $this->update($order_id,['deleted_at'=>NULL]);
        return $this->db->affectedRows()?'ok':'idle';
    }
    
    public function itemDisable( $order_id, $is_disabled ){
        if( !$this->permit($order_id,'w','disabled') ){
            return 'forbidden';
        }
        $this->allowedFields[]='is_disabled';
        $this->update(['order_id'=>$order_id],['is_disabled'=>$is_disabled?1:0]);
        return $this->db->affectedRows()?'ok':'idle';
    }

    public function listGet( $filter ){
        $this->filterMake($filter,false);
        $this->permitWhere('r');
        if($filter['order_store_id']??0){
            $this->where('order_store_id',$filter['order_store_id']);
        }
        if($filter['has_invoice']??0){
            $this->select("`order_data`->'$.invoice_link' invoice_link,`order_data`->'$.invoice_date' invoice_date,");
            $this->where('JSON_CONTAINS_PATH(order_data,"one","$.invoice_link")=1');
        }
        if($filter['order_group_type']??null){
            if($filter['order_group_type']=='active_only'){
                $user_id=session()->get('user_id');
                $this->where('ogl.group_type<>','system_finish');
                //$this->where("`ogl`.`group_type`='customer_cart' AND order_list.owner_id='$user_id' OR `ogl`.`group_type`<>'customer_cart'");
                $this->where('TIMESTAMPDIFF(DAY,order_list.created_at,NOW())<4');//only 3 days
            } else {
                $firstChar=substr($filter['order_group_type'],0,1);
                if( $firstChar=='!' ){
                    $this->where('ogl.group_type <>',substr($filter['order_group_type'],1));
                } else {
                    $this->where('ogl.group_type',$filter['order_group_type']);
                }
            }
        }

        $this->join('image_list',"image_holder='order' AND image_holder_id=order_id AND is_main=1",'left');
        $this->join('order_group_list ogl',"order_group_id=group_id",'left');
        $this->join('user_list ul',"user_id=order_list.owner_id");
        $this->join('store_list sl',"store_id=order_store_id",'left');

        $this->select("{$this->table}.order_id,{$this->table}.created_at,{$this->table}.order_sum_total");
        $this->select("group_id,group_name stage_current_name,group_type stage_current,user_phone,user_name,image_hash,store_name");
        $this->itemUserRoleCalc();
        if( $filter['user_role']??0 ){
            $this->havingIn('user_role',$filter['user_role']);
        }
        $this->orderBy('order_list.created_at','DESC');
        return $this->get()->getResult();
    }

    public function listCountGet(){
        $this->permitWhere('r');
        $this->whereNotIn('ogl.group_type',['system_finish','customer_deleted']);//
        //$this->having("`ogl`.`group_type`='customer_cart' AND user_role='customer' OR `ogl`.`group_type`<>'customer_cart'");
        $this->where('TIMESTAMPDIFF(DAY,order_list.created_at,NOW())<4');//only 3 days

        $this->itemUserRoleCalc();
        $this->join('order_group_list ogl',"order_group_id=group_id",'left');
        $this->select('COUNT(*) count,group_type');
        return $this->get()->getRow('count')??0;
    }
    
    public function listCreate(){
        return false;
    }
    
    public function listUpdate(){
        return false;
    }
    
    public function listDelete(){
        return false;
    }
    
    public function listPurge( $olderThan=1 ){
        $olderStamp= new \CodeIgniter\I18n\Time((-1*$olderThan)." hours");
        $this->where('deleted_at<',$olderStamp);
        return $this->delete(null,true);
    }
    
    
    /////////////////////////////////////////////////////
    //IMAGE HANDLING SECTION
    /////////////////////////////////////////////////////
    public function imageCreate( $data ){
        $data['is_disabled']=0;
        $data['owner_id']=session()->get('user_id');
        if( $this->permit($data['image_holder_id'], 'w') ){
            $ImageModel=model('ImageModel');
            $ok=$ImageModel->itemCreate($data);
            return $ok;
        }
        return 0;
    }
    
    public function imageDelete( $image_id ){
        $ImageModel=model('ImageModel');
        $image=$ImageModel->itemGet( $image_id );
        
        $order_id=$image->image_holder_id;
        if( !$this->permit($order_id,'w') ){
            return 'forbidden';
        }
        $ImageModel->itemDelete( $image_id );
        $ok=$ImageModel->itemPurge( $image_id );
        if( $ok ){
            return 'ok';
        }
        return 'idle';
    }
    
}