⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️
<b>🛵Курьер для покупателя не найден</b>
Покупатель <b><?=$customer->user_name?></b> пытался заказать доставку от продавца <b><?=$store->store_name?></b>, но доступных курьеров небыло

Продавец <?=$store->store_name?> <?=str_pad($store->store_phone, 12, "+", STR_PAD_LEFT)?>

Покупатель <?=$customer->user_name?> <?=str_pad($customer->user_phone, 12, "+", STR_PAD_LEFT)?>

Время <?=date('d.m.Y H:i:s')?>