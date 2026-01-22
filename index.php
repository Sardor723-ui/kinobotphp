<?php
error_reporting(0);

/* ================= SOZLAMALAR ================= */
$token = "8381839257:AAH-FSIdrA6oFbpA_er1vLAKwlN4-xVy1e8";
$main_admin = 1438837962; // sizning ID
$kanalcha = "@topkinoneww";
$bot = "@Topkinonewwbot";

/* ================= PAPKALAR ================= */
@mkdir("data");
@mkdir("data/users");
@mkdir("data/admin");
@mkdir("data/step");
@mkdir("data/kino");

if(!file_exists("data/kino/son.txt")) file_put_contents("data/kino/son.txt","0");
if(!file_exists("data/admin/admins.txt")) file_put_contents("data/admin/admins.txt",$main_admin);

/* ================= FUNKSIYALAR ================= */
function bot($method,$data=[]){
    global $token;
    $url = "https://api.telegram.org/bot$token/$method";
    $ch = curl_init();
    curl_setopt_array($ch,[
        CURLOPT_URL=>$url,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_POSTFIELDS=>$data
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res,true);
}

function isAdmin($id){
    $admins = file("data/admin/admins.txt", FILE_IGNORE_NEW_LINES);
    return in_array($id,$admins);
}

function joinchat($id){
    global $kanalcha;
    $res = bot("getChatMember",[
        "chat_id"=>$kanalcha,
        "user_id"=>$id
    ]);
    return ($res["result"]["status"]!="left");
}

/* ================= UPDATE ================= */
$update = json_decode(file_get_contents("php://input"),true);
$msg = $update["message"];
$cid = $msg["chat"]["id"];
$text = trim($msg["text"]);
$video = $msg["video"];

/* ================= USER ================= */
if($cid && !file_exists("data/users/$cid.txt")){
    file_put_contents("data/users/$cid.txt","1");
}

/* ================= START ================= */
if($text=="/start"){
    if(!joinchat($cid)){
        bot("sendMessage",[
            "chat_id"=>$cid,
            "text"=>"📛 Avval kanalga obuna bo‘ling!"
        ]);
        exit;
    }
    bot("sendMessage",[
        "chat_id"=>$cid,
        "text"=>"🎬 $botname\n\n🎥 Kino kodini yuboring:"
    ]);
}

/* ================= ADMIN PANEL ================= */
if($text=="/admin" && isAdmin($cid)){
    unlink("data/step/$cid.txt");
    bot("sendMessage",[
        "chat_id"=>$cid,
        "text"=>"👮 ADMIN PANEL",
        "reply_markup"=>json_encode([
            "keyboard"=>[
                ["➕ Kino qo‘shish"],
                ["🗑 Kino o‘chirish"],
                ["👤 Admin qo‘shish"],
                ["📊 Statistika"]
            ],
            "resize_keyboard"=>true
        ])
    ]);
}

/* ================= STATISTIKA ================= */
if($text=="📊 Statistika" && isAdmin($cid)){
    $users = count(scandir("data/users"))-2;
    $kino = file_get_contents("data/kino/son.txt");
    $admins = count(file("data/admin/admins.txt"));
    bot("sendMessage",[
        "chat_id"=>$cid,
        "text"=>"📊 Statistika\n\n👥 Users: $users\n🎬 Kinolar: $kino\n👮 Adminlar: $admins"
    ]);
}

/* ================= KINO QO‘SHISH ================= */
if($text=="➕ Kino qo‘shish" && isAdmin($cid)){
    file_put_contents("data/step/$cid.txt","add_kino");
    bot("sendMessage",[
        "chat_id"=>$cid,
        "text"=>"🎥 Videoni yuboring:"
    ]);
}

/* ================= KINO O‘CHIRISH ================= */
if($text=="🗑 Kino o‘chirish" && isAdmin($cid)){
    file_put_contents("data/step/$cid.txt","del_kino");
    bot("sendMessage",[
        "chat_id"=>$cid,
        "text"=>"🗑 O‘chirish uchun kino kodini yuboring:"
    ]);
}

/* ================= ADMIN QO‘SHISH ================= */
if($text=="👤 Admin qo‘shish" && isAdmin($cid)){
    file_put_contents("data/step/$cid.txt","add_admin");
    bot("sendMessage",[
        "chat_id"=>$cid,
        "text"=>"👤 Yangi admin ID yuboring:"
    ]);
}

$step = file_exists("data/step/$cid.txt") ? file_get_contents("data/step/$cid.txt") : "";

/* ================= STEP: KINO SAQLASH ================= */
if($step=="add_kino" && $video && isAdmin($cid)){
    $id = file_get_contents("data/kino/son.txt")+1;
    file_put_contents("data/kino/son.txt",$id);
    file_put_contents("data/kino/$id.txt",$video["file_id"]);
    unlink("data/step/$cid.txt");

    bot("sendMessage",[
        "chat_id"=>$cid,
        "text"=>"✅ Kino saqlandi!\n🎬 Kod: $id"
    ]);
}

/* ================= STEP: KINO O‘CHIRISH ================= */
if($step=="del_kino" && is_numeric($text) && isAdmin($cid)){
    if(file_exists("data/kino/$text.txt")){
        unlink("data/kino/$text.txt");
        unlink("data/step/$cid.txt");
        bot("sendMessage",[
            "chat_id"=>$cid,
            "text"=>"✅ Kino o‘chirildi (Kod: $text)"
        ]);
    } else {
        bot("sendMessage",[
            "chat_id"=>$cid,
            "text"=>"❌ Bunday kino yo‘q"
        ]);
    }
}

/* ================= STEP: ADMIN QO‘SHISH ================= */
if($step=="add_admin" && is_numeric($text) && isAdmin($cid)){
    $admins = file("data/admin/admins.txt", FILE_IGNORE_NEW_LINES);
    if(!in_array($text,$admins)){
        file_put_contents("data/admin/admins.txt",$text."\n",FILE_APPEND);
        unlink("data/step/$cid.txt");
        bot("sendMessage",[
            "chat_id"=>$cid,
            "text"=>"✅ Admin qo‘shildi!\nID: $text"
        ]);
    } else {
        bot("sendMessage",[
            "chat_id"=>$cid,
            "text"=>"⚠️ Bu ID allaqachon admin"
        ]);
    }
}

/* ================= KINO KO‘RISH ================= */
if(is_numeric($text) && file_exists("data/kino/$text.txt")){
    $file = file_get_contents("data/kino/$text.txt");
    bot("sendVideo",[
        "chat_id"=>$cid,
        "video"=>$file,
        "caption"=>"🎬 $botname"
    ]);
}
