
<b>Курьер <?=$user->user_name?></b>
ТС:<?=$courier->courier_vehicle??'не указан'?>, ИНН:<?=$courier->courier_tax_num??'не указан'?>, Доступных заданий:<b><u><?=$job_count?$job_count:'НЕТ'?></u></b>
Статус: <b><u><?= ($courier->status_type=='idle')?"💤 ОТБОЙ":($courier->status_type=='ready'?"🚦 ГОТОВ":"🚴 ЗАНЯТ")?></u></b>
<?php if($courier->status_type=='idle'): ?>
Чтобы начать смену транслируйте вашу геопозицию в чат
<?php endif; ?>