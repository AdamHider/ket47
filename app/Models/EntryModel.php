<?php
namespace App\Models;
use CodeIgniter\Model;

class EntryModel extends Model{
    use PermissionTrait;
    use FilterTrait;
    
    protected $table      = 'order_entry_list';
    protected $primaryKey = 'entry_id';
    protected $allowedFields = [
        'order_id',
        'product_id',
        'entry_text',
        'entry_quantity',
        'entry_self_price',
        'entry_price',
        'entry_comment',
        'deleted_at',
        'owner_id',
        'owner_ally_ids'
        ];

    protected $useSoftDeletes = true;
    protected $useTimestamps = false;
    
    protected $validationRules    = [
        'entry_text'        => 'min_length[10]',
        'entry_price' => 'greater_than_equal_to[1]',
        //'entry_quantity' => 'greater_than_equal_to[1]'
    ];
    
    public function itemGet( $entry_id ){
        $this->permitWhere('r');
        $this->where('entry_id',$entry_id);
        return $this->get()->getRow();
    }
    
    public function itemCreate($order_id,$product_id,$product_quantity){
        $OrderModel=model('OrderModel');
        $ProductModel=model('ProductModel');
        $OrderModel->permitWhere('w');
        $order_basic=$OrderModel->itemGet($order_id,'basic');
        $product_basic=$ProductModel->itemGet($product_id,'basic');
        if( !is_object($order_basic) || !is_object($product_basic) ){
            echo "order, product notfound or ";
            return 'forbidden';
        }
        if( !$product_quantity || $product_quantity<1 ){
            $product_quantity=1;
        }
        $new_entry=[
            'order_id'=>$order_id,
            'product_id'=>$product_id,
            'entry_text'=>"{$product_basic->product_name} {$product_basic->product_code}",
            'entry_quantity'=>$product_quantity,
            'entry_price'=>$product_basic->product_final_price,
            'owner_id'=>$order_basic->owner_id,
            'owner_ally_ids'=>$order_basic->owner_ally_ids
            ];
        try{
            $this->insert($new_entry);
            $entry_id=$this->db->insertID();
            return $entry_id;
        } catch( \Exception $e ){
            $this->where('order_id',$order_id);
            $this->where('product_id',$product_id);
            $this->set('entry_quantity',"(entry_quantity + $product_quantity)",false);
            $this->update();
            return 'updated';
        }
    }
    
    public function itemUpdate( $entry ){
        $this->permitWhere('w');
        $stock_check_sql="SELECT 
                product_quantity,
                entry_comment
            FROM
                order_entry_list
                    JOIN
                product_list USING (product_id)
            WHERE
                entry_id = {$entry->entry_id}";
        $stock=$this->query($stock_check_sql)->getRow();
        if( isset($entry->entry_quantity) && $entry->entry_quantity>$stock->product_quantity){
            $entry->entry_comment= preg_replace('/\[.+\]/u', '', $stock->entry_comment);
            $entry->entry_comment.="[Количество уменьшено с {$entry->entry_quantity} до {$stock->product_quantity}]";
            $entry->entry_quantity=$stock->product_quantity;
        }
        $this->update($entry->entry_id,$entry);
        return $this->db->affectedRows()>0?'ok':'idle';
    }
    
    public function itemDelete( $entry_id ){
        $this->permitWhere('w');
        $this->delete($entry_id);
        return $this->db->affectedRows()>0?'ok':'idle';
    }
    
    public function itemUnDelete( $entry_id ){
        $this->permitWhere('w');
        $this->update($entry_id,['deleted_at'=>NULL]);
        return $this->db->affectedRows()?'ok':'idle';
    }
    
    public function listGet( $order_id ){
        $this->permitWhere('r');
        $this->select("*");
        $this->select("ROUND(entry_quantity*entry_price,2) entry_sum");
        $this->where('order_id',$order_id);
        $entries=$this->get()->getResult();
        return $entries;
    }
    
    public function listSumGet( $order_id ){
        $this->permitWhere('r');
        $this->select("SUM(ROUND(entry_quantity*entry_price,2)) order_sum_total");
        $this->where('order_id',$order_id);
        $this->where('deleted_at IS NULL');
        return $this->get()->getRow();
    }
    
    public function listCreate(){
        return false;
    }
    
    public function listUpdate( $order_id, $entry_list ){
        return false;
    }
    
    public function listDelete(){
        return false;
    }
    
    public function listDeleteChildren( $order_id ){
        $OrderModel=model('OrderModel');
        if( !$OrderModel->permit($order_id,'w') ){
            return 'forbidden';
        }
        $this->where('deleted_at IS NOT NULL OR is_disabled=1');
        $this->where('order_id',$order_id);
        $this->delete(null,true);
        
        $this->where('deleted_at IS NULL AND is_disabled=0');
        $this->where('order_id',$order_id);
        $this->delete();
    }
    
    public function listUnDeleteChildren( $order_id ){
        $OrderModel=model('OrderModel');
        if( !$OrderModel->permit($order_id,'w') ){
            return 'forbidden';
        }
        $olderStamp= new \CodeIgniter\I18n\Time("-".APP_TRASHED_DAYS." days");
        $this->where('deleted_at>',$olderStamp);
        $this->where('order_id',$order_id);
        $this->set('deleted_at',NULL);
        $this->update();
    }

    
}