```markdown
# 🎉 QuizMeto — Bilgini Test Et, Eğlen, Yarış! 🧠🏆

![QuizMeto Logo](https://raw.githubusercontent.com/VEYOTEK/quizmeto/main/assets/images/site-logo-1744056841.png)

QuizMeto; kullanıcıların quiz oluşturup çözebildiği, sonuçlarını görebildiği, profil ve liderlik tabloları ile rekabet edebildiği modern, hafif ve anlaşılır bir PHP tabanlı quiz platformudur. Bu README, projeyi hızlıca kurmanız, özelliklerini anlamanız ve katkıda bulunmanız için emoji destekli, görselli ve okunması kolay bir rehber şeklinde hazırlanmıştır. 💫

---

## ✨ Öne Çıkan Özellikler
- 🔐 Kayıt / Giriş / Profil düzenleme (profil fotoğrafı yükleme)
- 📝 Quiz oluşturma, düzenleme (admin)
- 🧩 Quiz oynatma: rastgele soru sırası, çoktan seçmeli cevaplar
- ⏱️ Zaman sınırlı quiz desteği (geri sayım ve otomatik gönderme)
- 📊 Quiz sonuçları: puan, yüzde, sıralama, tamamlama süresi
- 🏅 Liderlik tablosu: genel veya quiz bazlı filtreleme
- 🛠️ Admin paneli: quiz, soru, kategori, kullanıcı ve skor yönetimi
- 🛡️ Temel güvenlik: PDO prepared statements, CSRF token kullanımı

---

## 🖼️ Projeye Ait Görseller (repo içinden)
Aşağıda projede hali hazırda bulunan görselleri görebilirsiniz. Bunlar doğrudan repoda yer alan varlıklar (assets/images) kullanılarak eklendi.

Logo:
![Site Logo](https://raw.githubusercontent.com/VEYOTEK/quizmeto/main/assets/images/site-logo-1744056841.png)

Boş quiz ekranı / placeholder:
![No Quiz](https://raw.githubusercontent.com/VEYOTEK/quizmeto/main/assets/images/no-quiz.svg)

Varsayılan avatar:
![Default Avatar](https://raw.githubusercontent.com/VEYOTEK/quizmeto/main/assets/images/default-avatar.png)

Favicon (küçük görsel):
![Favicon](https://raw.githubusercontent.com/VEYOTEK/quizmeto/main/assets/images/favicon-1744056841.ico)

Not: Eğer repoda özel **ekran görüntüleri (screenshots)** yoksa, isterseniz ben örnek ara yüz görüntüleri (mockup) hazırlayıp README'ye ekleyebilirim. Veya siz çalışma ekranından birkaç ekran görüntüsü yüklerseniz onları README'ye yerleştiririm. 📸

---

## 🚀 Hızlı Kurulum (Local)
1. Gereksinimler:
   - PHP 8+ ve gerekli PHP uzantıları (pdo_mysql vb.)
   - MySQL / MariaDB
   - Web sunucusu (Apache / Nginx) veya PHP built-in server
2. Depoyu klonlayın:
   git clone https://github.com/VEYOTEK/quizmeto.git
3. Veritabanı oluşturun ve SQL dump'ını import edin:
   - Dosya: `quizmeto (1).sql` (repoda mevcut)
4. `config/db.php` içindeki veritabanı bağlantı bilgilerini güncelleyin (host, db, user, pass).
5. Dosya izinlerini kontrol edin:
   - `assets/uploads/` dizini yazılabilir olmalı (profil resimleri için).
6. Tarayıcıda projeyi açın: http://localhost/quizmeto

---

## 🔧 Temel Dosya Yapısı
- index.php — Anasayfa, popüler quizler, üst kullanıcılar
- register.php / login.php / logout.php — Kullanıcı işlemleri
- quizzes.php — Quiz listesi, filtreleme, sayfalama
- quiz.php — Quiz oynatma ekranı (JS ile ilerleme, timer)
- submit-quiz.php — Gönderim ve skor kaydetme
- quiz-result.php — Detaylı sonuç ekranı
- profile.php — Profil görüntüleme ve güncelleme
- my-quizzes.php — Kullanıcının katıldığı/oluşturduğu quizler
- leaderboard.php — Liderlik tablosu
- admin/ — Yönetici paneli sayfaları
- config/db.php — PDO ile DB bağlantı
- includes/functions.php — Yardımcı fonksiyonlar (CSRF, formatlama, hesaplama)
- quizmeto (1).sql — DB şeması ve örnek veriler

---

## 🔐 Güvenlik Önerileri
- Tüm formlarda CSRF tokenlar zaten kullanılıyor — her formu kontrol edin.
- Dosya yükleme (profil resmi) için MIME tipi ve maksimum boyut kontrolleri mevcut ama ek kontroller (resim işleme, virüs taraması) ekleyin.
- Production için HTTPS kesinlikle zorunlu olmalı.
- Hataları kullanıcıya gösterirken ham DB hatası sızdırmamaya dikkat edin.
- Rate limiting / login brute-force koruması eklenmesi önerilir.

---

## 🧩 Admin Bilgileri
- Admin hesabı SQL dump içinde mevcut (kontrol etmek için `users` tablosuna bakın). 🌟
- Admin paneline erişmek için kullanıcı rolü `admin` olmalıdır.
- Admin panelinden quiz silme, kullanıcı yönetimi vb. yapılabilir.

---

## 🤝 Katkıda Bulunma
- Fork → branch → değişiklik → PR
- Önerilen geliştirmeler:
  - Unit test ekleme
  - API uçları (REST/GraphQL) ile frontend ayrıştırma
  - Websocket ile canlı liderlik tabloları
  - Çoklu dil desteği (i18n)

---

## 📝 Lisans
Projede lisans belirtilmemişse lütfen uygun bir lisans (ör. MIT) ekleyin. Lisans ekleyince README'ye lisans bölümünü güncellerim. 📜

---

İsterseniz:
- README'yi repo köküne yükleyebilirim (README.md olarak). ✅
- Eksikse veya isterseniz gerçek ekran görüntüleri ekleyebilmeniz için örnek bir "screenshots/" dizini ve şablon da oluşturabilirim. 🖼️
- Veya sizin yükleyeceğiniz ekran görüntülerini README'ye otomatik ekleyecek bir PR hazırlarım.

Hangi görselleri README'ye eklememi istersiniz: repo içindeki mevcut varlıklar yeterli mi yoksa sizin sağladığınız ekran görüntülerini kullanayım mı? 📷✨
```
