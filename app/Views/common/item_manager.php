<?=view('home/header')?>
<div style="padding: 20px;">
    <div class="search_bar">
        <input type="search" placeholder="Filter">
    </div>
    <div class="item_list"></div>
</div>
<style>
    .item_list{
        
    }
</style>
<script type="text/javascript">
    ItemList={
        init:function (){
            $('.item_list').on('change',function(e){
                var $input=$(e.target);
                var name_parts=$input.attr('name').split('.');
                var name=name_parts[0];
                var item_id=name_parts[1];
                var subtype=name_parts[2];
                var value=ItemList.val($input);
                if( subtype==='date' ){
                    value=value+' '+(ItemList.val( $(`input[name='${name}.${item_id}.time']`) )||'00:00')+':00';
                }
                if( subtype==='time' ){
                    value=ItemList.val( $(`input[name='${name}.${item_id}.date']`) )+' '+value+':00';
                }
//                if( name==='item_group_id' ){
//                    ItemList.saveItemMemberGroup(item_id,subtype,value);
//                } else {
//                    ItemList.saveItem(item_id,name,value);
//                }
            });
            $('.search_bar').on('input',function(e){
                ItemList.reload();
            });
            ItemList.reload();
        },
        val:function( $input ){
            return $input.attr('type')==='checkbox'?($input.is(':checked')?1:0):$input.val();
        },
        saveItem:function (item_id,name,value){
            $.post('/<?=$ItemName?>/itemUpdate',{item_id,name,value});
        },
        saveItemMemberGroup:function (item_id,item_group_id,value){
            $.post('/<?=$ItemName?>MemberGroup/itemUpdate',{item_id,item_group_id,value});
        },
        deleteItem:function( item_id ){
            $.post('/<?=$ItemName?>/itemDelete',{item_id}).done(ItemList.reload);
        },
        undeleteItem:function( item_id ){
            var name='deleted_at';
            $.post('/<?=$ItemName?>/itemUpdate',{item_id,name}).done(ItemList.reload);
        },
        reload_promise:null,
        reload:function(){
            if(ItemList.reload_promise){
                ItemList.reload_promise.abort();
            }
            var name_query=$('.search_bar input').val();
            var name_query_fields='<?=$name_query_fields?>';
            var filter={
                name_query,
                name_query_fields,
                limit:30
            };
            ItemList.reload_promise=$.post('/Home/<?=$item_name?>_list',filter).done(function(response){
                $('.item_list').html(response);
            }).fail(function(error){
                $('.item_list').html(error);
            });
        }
    };
    $(ItemList.init);
</script>
<?=view('home/footer')?>