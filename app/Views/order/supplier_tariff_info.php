<?php if( isset($order_data->delivery_by_store) || isset($order_data->pickup_by_customer) ): ?>
    <p>Не забудьте связаться с покупателем для уточнения деталей. При оформлении заказа покупатель выбрал такие параметры</p>

    <?php if( isset($order_data->pickup_by_customer) ): ?>
        <p>Способ получения заказа: <b>САМОВЫВОЗ</b> 🚶. 
    <?php endif;?>

    <?php if( isset($order_data->delivery_by_store) ): ?>
        <p>Доставка заказа: <b>КУРЬЕР ПРОДАВЦА</b> 🛵.  
    <?php endif;?>

    <?php if( isset($order_data->payment_card_fixate_sum) ): ?>
        <p>Оплата: <b>ПРЕДОПЛАЧЕН БАНКОВСКОЙ КАРТОЙ</b> 💳. После нажатия "Завершить подготовку", у покупателя будет списана итоговая сумма заказа. 
    <?php endif;?>

    <?php if( isset($order_data->payment_by_cash_store) ): ?>
        <p>Оплата: <b>НАЛИЧНЫМИ</b> 💵. Заказ не оплачен, согласуйте детали с покупателем. 
    <?php endif;?>
<?php endif;?>