🛵🛵🛵🛵🛵🛵🛵🛵🛵🛵🛵
<b>Регистрация нового курьера</b>
Пользователь <b><?=$user_name?></b> заполнил анкету курьера

<a href="<?=getenv('app.frontendUrl')?>user/courier-dashboard?courier_id=<?=$courier_id?>">Анкета курьера</a>

Пользователь: <b><?=$user_name?></b> <?=str_pad($user_phone, 12, "+", STR_PAD_LEFT)?>