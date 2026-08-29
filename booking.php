<?php
require 'includes/database.php';
require 'includes/auth.php';
requireLogin('customer');

$sid=(int)($_GET['service_id']??$_POST['service_id']??0);
$st=$pdo->prepare("SELECT sv.*,s.name shop_name,s.capacity,s.open_time,s.close_time FROM services sv JOIN shops s ON s.id=sv.shop_id WHERE sv.id=? AND sv.active=1");
$st->execute([$sid]);
$svc=$st->fetch();
if(!$svc) exit('Service not found');

function slotAvailability(PDO $pdo, array $svc, string $date): array {
    if(!$date) return [];
    $open = new DateTime($date.' '.$svc['open_time']);
    $close = new DateTime($date.' '.$svc['close_time']);
    $duration = max(30,(int)$svc['duration_minutes']);
    $slots=[];
    for($t=clone $open; $t < $close; $t->modify('+30 minutes')){
        $end=(clone $t)->modify('+'.$duration.' minutes');
        if($end>$close) break;
        $candidateStart=$t->format('H:i:s');
        $candidateEnd=$end->format('H:i:s');
        $q=$pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN services sv ON sv.id=b.service_id WHERE b.shop_id=? AND b.booking_date=? AND b.booking_status<>'cancelled' AND b.booking_time < ? AND ADDTIME(b.booking_time, SEC_TO_TIME(sv.duration_minutes*60)) > ?");
        $q->execute([$svc['shop_id'],$date,$candidateEnd,$candidateStart]);
        $used=(int)$q->fetchColumn();
        $remaining=max(0,(int)$svc['capacity']-$used);
        $slots[]=['time'=>$t->format('H:i'),'remaining'=>$remaining,'full'=>$remaining<=0];
    }
    return $slots;
}

if(isset($_GET['slots'])){
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(slotAvailability($pdo,$svc,$_GET['date']??''),JSON_UNESCAPED_UNICODE);
    exit;
}

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $date=$_POST['booking_date']??'';
    $time=$_POST['booking_time']??'';
    $valid=false;
    foreach(slotAvailability($pdo,$svc,$date) as $slot){if($slot['time']===$time && !$slot['full']){$valid=true;break;}}
    if(!$valid){
        $error='ช่วงเวลานี้เต็มหรือไม่อยู่ในเวลาที่ร้านเปิด กรุณาเลือกเวลาใหม่';
    } else {
        $deposit=((float)$svc['price']<=200)?50:100;
        $in=$pdo->prepare("INSERT INTO bookings(customer_id,shop_id,service_id,booking_date,booking_time,note,service_price,deposit_amount) VALUES(?,?,?,?,?,?,?,?)");
        $in->execute([$_SESSION['user']['id'],$svc['shop_id'],$svc['id'],$date,$time.':00',$_POST['note']??'',$svc['price'],$deposit]);
        header('Location: deposit.php?id='.$pdo->lastInsertId());exit;
    }
}
require 'includes/header.php';
?>
<main class="wrap">
  <form class="form card" method="post" id="bookingForm">
    <span class="eyebrow">จองบริการ</span>
    <h2 style="margin-top:0"><?=htmlspecialchars($svc['name'])?></h2>
    <p><?=htmlspecialchars($svc['shop_name'])?> • <?=number_format($svc['price'],0)?> บาท • ใช้เวลาประมาณ <?=(int)$svc['duration_minutes']?> นาที</p>
    <p class="muted">ร้านรองรับพร้อมกันได้ <strong><?=(int)$svc['capacity']?> คน</strong> และเลือกเวลาเป็นช่วงละ 30 นาที</p>
    <?php if($error):?><p class="login-error"><?=htmlspecialchars($error)?></p><?php endif;?>
    <input type="hidden" name="service_id" value="<?=$sid?>">
    <label>วันที่ต้องการจอง</label>
    <input type="date" id="booking_date" name="booking_date" min="<?=date('Y-m-d')?>" value="<?=htmlspecialchars($_POST['booking_date']??'')?>" required>
    <label>เวลา</label>
    <div id="slotStatus" class="muted" style="margin:6px 0 10px">เลือกวันที่ก่อน ระบบจะแสดงเวลาที่ว่าง</div>
    <div id="timeSlots" class="time-slot-grid"></div>
    <input type="hidden" id="booking_time" name="booking_time" value="<?=htmlspecialchars($_POST['booking_time']??'')?>" required>
    <label style="margin-top:18px;display:block">ข้อมูลเพิ่มเติมถึงเจ้าของร้าน</label>
    <textarea name="note" rows="4" placeholder="เช่น ต้องการทรงผมแบบไหน หรือมีรายละเอียดที่อยากแจ้งล่วงหน้า"><?=htmlspecialchars($_POST['note']??'')?></textarea>
    <button class="btn" style="width:100%">ไปชำระมัดจำ</button>
  </form>
</main>
<script>
const dateInput=document.getElementById('booking_date');
const grid=document.getElementById('timeSlots');
const hidden=document.getElementById('booking_time');
const status=document.getElementById('slotStatus');
async function loadSlots(){
  if(!dateInput.value){grid.innerHTML='';return;}
  status.textContent='กำลังตรวจสอบเวลาว่าง...'; grid.innerHTML=''; hidden.value='';
  try{
    const res=await fetch(`booking.php?service_id=<?=$sid?>&slots=1&date=${encodeURIComponent(dateInput.value)}`);
    const slots=await res.json();
    const available=slots.filter(s=>!s.full).length;
    status.textContent=available?`มี ${available} ช่วงเวลาที่สามารถจองได้`:'วันนี้ไม่มีช่วงเวลาว่าง';
    grid.innerHTML=slots.map(s=>`<button type="button" class="time-slot ${s.full?'full':''}" ${s.full?'disabled':''} data-time="${s.time}"><strong>${s.time}</strong><small>${s.full?'เต็มแล้ว':'เหลือ '+s.remaining+' ที่'}</small></button>`).join('');
    grid.querySelectorAll('.time-slot:not(.full)').forEach(btn=>btn.addEventListener('click',()=>{
      grid.querySelectorAll('.time-slot').forEach(b=>b.classList.remove('selected'));
      btn.classList.add('selected'); hidden.value=btn.dataset.time;
    }));
  }catch(e){status.textContent='ไม่สามารถโหลดเวลาว่างได้ กรุณาลองใหม่';}
}
dateInput.addEventListener('change',loadSlots); if(dateInput.value) loadSlots();
document.getElementById('bookingForm').addEventListener('submit',e=>{if(!hidden.value){e.preventDefault();alert('กรุณาเลือกเวลาที่ต้องการจอง');}});
</script>
<?php require 'includes/footer.php';?>
