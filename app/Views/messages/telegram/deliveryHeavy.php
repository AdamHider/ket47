<?php if($delivery_heavy_level): ?>
<b>⛈️ Повышенная доставка</b>
<pre>Установленный коэффициент</pre><b><?=$delivery_heavy_level?></b>
<pre>Повышение доставки</pre><b><?=$delivery_heavy_cost?></b>
<pre>Бонус курьера</pre><b><?=$delivery_heavy_bonus?></b>
<?php else: ?>
<b>🌤️ Нормальная доставка</b>
Стоимость доставки соответствует тарифу
<?php endif; ?>
