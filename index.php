<?php
require 'includes/database.php';

$lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
$lng = filter_input(INPUT_GET, 'lng', FILTER_VALIDATE_FLOAT);
$hasLocation = ($lat !== false && $lat !== null && $lng !== false && $lng !== null);

$ratingSql = "SELECT s.*, (SELECT COALESCE(AVG(r.rating),0) FROM reviews r WHERE r.shop_id=s.id) AS avg_rating, (SELECT COUNT(*) FROM reviews r2 WHERE r2.shop_id=s.id) AS reviews FROM shops s ORDER BY avg_rating DESC,reviews DESC,s.id DESC LIMIT 5";
$rating = $pdo->query($ratingSql)->fetchAll();

$near = [];
if ($hasLocation) {
    $sql = "SELECT s.*, ROUND((6371 * 2 * ASIN(SQRT(POWER(SIN(RADIANS(s.latitude - ?) / 2),2) + COS(RADIANS(?)) * COS(RADIANS(s.latitude)) * POWER(SIN(RADIANS(s.longitude - ?) / 2),2)))), 3) AS distance FROM shops s WHERE s.latitude IS NOT NULL AND s.longitude IS NOT NULL ORDER BY distance ASC LIMIT 5";
    $q = $pdo->prepare($sql);
    $q->execute([$lat, $lat, $lng]);
    $near = $q->fetchAll();
}

$shopCount = (int)$pdo->query("SELECT COUNT(*) FROM shops")->fetchColumn();
$serviceCount = (int)$pdo->query("SELECT COUNT(*) FROM services WHERE active=1")->fetchColumn();

require 'includes/header.php';
?>
<main class="wrap home-wrap">
  <section class="hero minimal-hero">
    <div class="hero-copy">
      <span class="eyebrow">จองง่าย · ดูราคาได้ก่อน · เลือกเวลาที่สะดวก</span>
      <h1>ดูแลตัวเองได้ง่ายขึ้น<br>เริ่มจากร้านที่ใช่ใกล้คุณ</h1>
      <p class="muted">รวมคลินิก ร้านทำผม ร้านทำเล็บ และสปาไว้ในที่เดียว พร้อมคะแนน รีวิว ราคา และสถานะการจองที่ดูง่าย</p>
      <div class="hero-actions">
        <button class="btn location-btn" id="nearMeBtn" type="button" onclick="getLocationAndLoad()">⌖ ค้นหาร้านใกล้ฉัน</button>
        <a class="btn btn-light" href="#categories">ดูหมวดหมู่</a>
      </div>
      <div id="locationMessage" class="location-message" role="status" aria-live="polite">
        <?php if ($hasLocation && $near): ?>พบ <?=count($near)?> ร้านที่ใกล้คุณที่สุดแล้ว<?php endif; ?>
      </div>
    </div>
    <div class="hero-summary">
      <div><strong><?=number_format($shopCount)?></strong><span>ร้านในระบบ</span></div>
      <div><strong><?=number_format($serviceCount)?></strong><span>บริการให้เลือก</span></div>
      <div><strong>4</strong><span>หมวดหมู่หลัก</span></div>
    </div>
  </section>

  <section id="categories" class="home-section">
    <div class="section-heading"><div><span class="eyebrow">เลือกตามความต้องการ</span><h2>วันนี้อยากดูแลอะไร?</h2></div></div>
    <div class="category-grid">
      <a class="category-card" href="category.php?c=คลินิกเสริมความงาม"><span class="category-icon">✦</span><div><strong>คลินิกเสริมความงาม</strong><small>ผิวหน้า ทรีตเมนต์ และการดูแลความงาม</small></div><span class="arrow">›</span></a>
      <a class="category-card" href="category.php?c=ร้านทำผมและเสริมสวย"><span class="category-icon">✂</span><div><strong>ร้านทำผมและเสริมสวย</strong><small>ตัด ทำสี ดัด ยืด และจัดแต่งทรงผม</small></div><span class="arrow">›</span></a>
      <a class="category-card" href="category.php?c=ร้านทำเล็บและขนตา"><span class="category-icon">◇</span><div><strong>ร้านทำเล็บและขนตา</strong><small>เล็บเจล ต่อเล็บ ขนตา และลิฟติ้ง</small></div><span class="arrow">›</span></a>
      <a class="category-card" href="category.php?c=สปาและร้านนวด"><span class="category-icon">☘</span><div><strong>สปาและร้านนวด</strong><small>นวดผ่อนคลาย อโรม่า และสปา</small></div><span class="arrow">›</span></a>
    </div>
  </section>

  <section class="home-section" id="nearby">
    <div class="section-heading">
      <div><span class="eyebrow">เดินทางสะดวก</span><h2>ร้านใกล้ฉัน</h2><p class="muted">เรียงจากระยะทางจริงตามพิกัดของอุปกรณ์คุณ</p></div>
      <?php if ($hasLocation): ?><button class="text-btn" type="button" onclick="getLocationAndLoad()">ค้นหาใหม่</button><?php endif; ?>
    </div>

    <?php if (!$hasLocation): ?>
      <div class="location-empty">
        <div class="location-empty-icon">⌖</div>
        <div><strong>กด “ค้นหาร้านใกล้ฉัน” เพื่อเริ่ม</strong><p>เบราว์เซอร์จะขออนุญาตใช้ตำแหน่งของคุณเพียงเพื่อคำนวณระยะทาง</p></div>
        <button class="btn" type="button" onclick="getLocationAndLoad()">ใช้ตำแหน่งของฉัน</button>
      </div>
    <?php elseif (!$near): ?>
      <div class="location-empty"><div class="location-empty-icon">!</div><div><strong>ยังไม่พบร้านที่มีพิกัด</strong><p>ตรวจสอบ latitude / longitude ของร้านในฐานข้อมูล</p></div></div>
    <?php else: ?>
      <div class="shop-row">
        <?php foreach($near as $s): ?>
          <a class="shop-card" href="shop.php?id=<?=$s['id']?>">
            <img class="shop-img" src="<?=htmlspecialchars($s['image'] ?: 'https://placehold.co/600x360?text=BeautiGo')?>" alt="<?=htmlspecialchars($s['name'])?>">
            <div class="shop-card-body"><span class="badge"><?=htmlspecialchars($s['category'])?></span><h3><?=htmlspecialchars($s['name'])?></h3><div class="shop-meta"><span>⌖ <?=number_format((float)$s['distance'],1)?> กม.</span><span>เปิด <?=substr($s['open_time'],0,5)?> น.</span></div></div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="home-section">
    <div class="section-heading"><div><span class="eyebrow">คนใช้บริการชื่นชอบ</span><h2>ร้านคะแนนสูงสุด</h2><p class="muted">5 ร้านที่มีคะแนนเฉลี่ยดีที่สุดจากรีวิวในระบบ</p></div></div>
    <div class="shop-row">
      <?php foreach($rating as $s): ?>
        <a class="shop-card" href="shop.php?id=<?=$s['id']?>">
          <img class="shop-img" src="<?=htmlspecialchars($s['image'] ?: 'https://placehold.co/600x360?text=BeautiGo')?>" alt="<?=htmlspecialchars($s['name'])?>">
          <div class="shop-card-body"><span class="badge"><?=htmlspecialchars($s['category'])?></span><h3><?=htmlspecialchars($s['name'])?></h3><div class="shop-meta"><span class="stars">★ <?=number_format((float)$s['avg_rating'],1)?></span><span><?=number_format((int)$s['reviews'])?> รีวิว</span></div></div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="how-section">
    <div><span class="eyebrow">ใช้เวลาไม่นาน</span><h2>จองคิวใน 3 ขั้นตอน</h2></div>
    <div class="steps"><div><span>1</span><strong>เลือกร้านและบริการ</strong><small>ดูราคาและรายละเอียดก่อนจอง</small></div><div><span>2</span><strong>เลือกวันและเวลา</strong><small>เลือกช่วงเวลาที่คุณสะดวก</small></div><div><span>3</span><strong>ชำระมัดจำ</strong><small>ติดตามสถานะได้จากเมนูด้านซ้าย</small></div></div>
  </section>
</main>
<?php require 'includes/footer.php'; ?>
