<?=view('common/item_manager',[
    'item_name'=>'order',
    'ItemName'=>'Order',
    'dontCreateWithName'=>1,
    'name_query_fields'=>'user_phone,user_name,order_description',
    'html_before'=>view('common/store_selector',['use_all_stores'=>1,'store_click_handler'=>'
        ItemList.addItemRequest.order_store_id=store_id;
        ItemList.reloadFilter.order_store_id=store_id;
        ItemList.reload();
        ']),
    'html_after'=>''
    ])?>