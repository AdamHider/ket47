<?php if( isset($order_data->delivery_by_store) || isset($order_data->pickup_by_customer) ): ?>
    <p>Не забудьте связаться с покупателем для уточнения деталей. При оформлении заказа покупатель выбрал такие параметры:</p>

    <br/>
    <?php if( isset($order_data->payment_card_fixate_sum) ): ?>
        <h1>Оплата:</h1> 
        <p><b>ПРЕДОПЛАЧЕН КАРТОЙ</b>💳.</p>
        <p>После нажатия "Завершить подготовку", у покупателя будет списана итоговая сумма заказа.</p>
    <?php endif;?>
    <?php if( isset($order_data->payment_by_cash_store) ): ?>
        <h1>Оплата:</h1> 
        <p><b>НАЛИЧНЫМИ</b>💵.</p>
        <p>Заказ не оплачен, согласуйте детали с покупателем.</p>
    <?php endif;?>

    <br/>
    <?php if( isset($order_data->pickup_by_customer) ): ?>
        <h1>Способ получения:</h1>
        <p><b>САМОВЫВОЗ</b>🚶.</p>
    <?php endif;?>
    <?php if( isset($order_data->delivery_by_store) ): ?>
        <h1>Доставка:</h1> 
        <p><b>КУРЬЕР ПРОДАВЦА</b>🛵.</p>
    <?php endif;?>
<?php endif;?>