CKEditor 5 (CDN kullanılmadan) bu klasörden sunulur.

Mevcut dosyalar Botble projesinden kopyalanmıştır. Konsolda "Symbol.toStringTag
is read-only" veya "unreachable code after return" görürseniz, resmi build ile
değiştirmeniz önerilir (yine CDN kullanılmaz):

  1. https://ckeditor.com/ckeditor-5/download/ adresinden "Classic editor"
     build'ini indirin (Online builder veya npm).
  2. İndirilen build içindeki build/ klasöründeki dosyaları (ckeditor.js,
     ckeditor.css, translations/ vb.) bu klasöre kopyalayın.
  3. Ana script adı farklıysa (örn. ckeditor.js) base.html.twig içinde
     ui-vendor/ckeditor5/ckeditor.js yolunu buna göre güncelleyin.

Kullanım:
  Admin panel → Konu ve Post Ayarları → "Metin editörü (konu/mesaj)" → CKEditor 5.

Klasör yapısı:
  ckeditor.js         = Ana build (ClassicEditor)
  content-styles.css   = İçerik stilleri (opsiyonel)
  translations/        = Dil dosyaları (tr, en, vb.)

Not: "SameSite" çerez uyarısı ve YouTube embed ile ilgili bölümlenmiş çerez
mesajları tarayıcı/üçüncü taraf kaynaklıdır; uygulama kodundan giderilmez.
