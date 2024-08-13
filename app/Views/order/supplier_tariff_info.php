<?php if( isset($order_data->delivery_by_store) || isset($order_data->pickup_by_customer) ): ?>
    <p>Не забудьте связаться с покупателем для уточнения деталей. При оформлении заказа покупатель выбрал такие параметры:</p>
    
    <br/>
    <?php if( isset($order_data->pickup_by_customer) ): ?>
        <h2>Способ получения заказа</h2> <b>САМОВЫВОЗ</b>🚶. <br>
    <?php endif;?>

    <br/>
    <?php if( isset($order_data->delivery_by_store) ): ?>
        <h2>Доставка заказа</h2> <b>КУРЬЕР ПРОДАВЦА</b>🛵.  <br>
    <?php endif;?>

    <br/>
    <?php if( isset($order_data->payment_card_fixate_sum) ): ?>
        <h2>Оплата</h2> <b>ПРЕДОПЛАЧЕН БАНКОВСКОЙ КАРТОЙ</b>💳. После нажатия "Завершить подготовку", у покупателя будет списана итоговая сумма заказа. <br>
    <?php endif;?>

    <br/>
    <?php if( isset($order_data->payment_by_cash_store) ): ?>
        <h2>Оплата</h2> <b>НАЛИЧНЫМИ</b> 💵. Заказ не оплачен, согласуйте детали с покупателем. <br>
    <?php endif;?>
<?php endif;?>