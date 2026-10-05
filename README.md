<img src="https://ps.w.org/smart-sitemap-generator/assets/banner-1544x500.png?rev=3729079" alt="Smart Sitemap Generator" style="float: left; width:100%; margin-bottom:30px" />

# Smart Sitemap Generator

[![CI](https://github.com/optimisthub/smart-sitemap-generator/actions/workflows/ci.yml/badge.svg)](https://github.com/optimisthub/smart-sitemap-generator/actions/workflows/ci.yml)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/plugins/smart-sitemap-generator/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-8892BF.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

| | |
|---|---|
| **Minimum WordPress Sürümü** | 6.0 |
| **Test Edilen WordPress Sürümü** | 7.1 |
| **PHP** | 7.4+ |
| **Stabil Versiyon** | 2.0.1 |
| **Lisans** | GPLv2 ya da daha sonrası |
| **Lisans URI** | https://www.gnu.org/licenses/gpl-2.0.html |

Smart Sitemap Generator / Akıllı Site Haritası Oluşturucu eklentisi; yazılarınız, sayfalarınız ve özel yazı tipleriniz için otomatik olarak XML site haritaları ve bunları birleştiren bir site haritası dizini oluşturur.

Site haritaları statik dosyalar olarak yazılır; bu sayede sunulması neredeyse sıfır maliyetlidir ve ziyaretçilerinizi yavaşlatmadan üretilir.

## Nerede bulunur?

Etkinleştirdikten sonra site haritası dizini şu adreste yayınlanır:

```
https://example.com/wp-content/uploads/sitemaps/sitemap-index.xml
```

Bu adresi Google Search Console, Bing Webmaster Tools ve Yandex Webmaster'a gönderin.

## Özellikler

- Seçtiğiniz her yazı tipi için ayrı site haritası üretir
- Hepsini birleştiren bir site haritası dizini (`sitemap-index.xml`) oluşturur
- Her URL için `lastmod`, `changefreq` ve `priority` ekler
- Büyük siteleri 2.000 URL'lik dosyalara böler
- İçerik yayınladığınızda veya güncellediğinizde otomatik yeniden üretir
- Seçtiğiniz aralıkta yeniden üretir: 24 saat, 1 hafta, 15 gün veya 30 gün
- Ayar sayfasını kaydettiğinizde anında yeniden üretir
- Çıktı, resmi sitemaps.org XSD şemasına uygundur
- Site haritaları `wp-content/uploads/sitemaps/` altında saklanır (normal hosting'de yazılabilir)
- Dizin listelemesini engellemek için `Options -Indexes` ekler
- Kaldırıldığında üretilen tüm dosyaları siler

## Kurulum

### WordPress üzerinden kurulum

1. Eklentiler sayfasından **Yeni Ekle**'ye tıklayın
2. `Smart Sitemap Generator` araması yapın
3. **Kur** ve ardından **Etkinleştir**'e tıklayın
4. **Ayarlar > Smart Sitemap** menüsünden yazı tiplerini ve yenileme aralığını seçin

### Elle kurulum

1. Zip dosyasını indirip açın, içinden çıkan `smart-sitemap-generator` klasörünü `/wp-content/plugins/` dizinine yükleyin
2. Eklentiler menüsünden `Smart Sitemap Generator` eklentisini etkinleştirin
3. **Ayarlar > Smart Sitemap** menüsünden yapılandırın

### Composer ile kurulum

Bu paket Packagist'te yayınlanmadığı için önce GitHub deposunu VCS deposu
olarak tanıtmanız gerekir:

```bash
composer config repositories.optimisthub-ssg vcs https://github.com/optimisthub/smart-sitemap-generator
composer require optimisthub/smart-sitemap-generator
```

### Bedrock ile kurulum

[Bedrock](https://roots.io/bedrock/) kullanıyorsanız eklenti, `type`
alanı `wordpress-plugin` olduğu için `composer/installers` tarafından
doğru dizine yerleştirilir. Projenizin `composer.json` dosyasına şunları
ekleyin:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/optimisthub/smart-sitemap-generator"
        }
    ],
    "require": {
        "optimisthub/smart-sitemap-generator": "^2.0"
    },
    "extra": {
        "installer-paths": {
            "web/app/plugins/{$name}/": ["type:wordpress-plugin"]
        }
    },
    "config": {
        "allow-plugins": {
            "composer/installers": true
        }
    }
}
```

Ardından:

```bash
composer update optimisthub/smart-sitemap-generator
```

Eklenti `web/app/plugins/smart-sitemap-generator/` dizinine kurulur.
Etkinleştirmek için:

```bash
wp plugin activate smart-sitemap-generator
```

> **Not:** `installer-paths` tanımı olmadan eklenti `vendor/` altına
> kurulur ve WordPress onu görmez.

## Sıkça Sorulan Sorular

### Site haritası adresini nereden bulurum?

Eklenti ayar sayfasının üstünde gösterilir: **Ayarlar > Smart Sitemap**.

### Yoast SEO, Rank Math veya All in One SEO ile kullanabilir miyim?

Evet. Başka bir eklenti zaten site haritası üretiyorsa, bu eklentinin üretimini kapatabilir veya sadece ona ait adresi arama motorlarına göndermeyebilirsiniz. İkisini birlikte çalıştırmak zararsızdır ancak gereksizdir.

### Hangi yazı tipleri dahil edilir?

Yalnızca ayarlardan işaretlediğiniz genel (public) yazı tipleri. Ekler (attachment) kasıtlı olarak hariç tutulur, çünkü medya dosyaları adreslenebilir içerik sayfaları değildir.

### `priority` veya `changefreq` değerlerini özelleştirebilir miyim?

Evet:

```php
add_filter( 'smartsitemap_priority', function ( $priority, $post ) {
    return 'page' === $post->post_type ? '1.0' : $priority;
}, 10, 2 );

add_filter( 'smartsitemap_changefreq', function ( $changefreq, $post ) {
    return 'daily';
}, 10, 2 );
```

### Site haritaları yenilenmiyor, neyi kontrol etmeliyim?

WP-Cron'un çalıştığından emin olun. Düşük trafikli sitelerde veya `DISABLE_WP_CRON` tanımlı sitelerde, `wp-cron.php`'yi gerçek bir sunucu cron görevinden çağırmanız gerekir. Ayar sayfasını kaydederek de anında yeniden üretim başlatabilirsiniz.

### Multisite'ta çalışır mı?

Evet. Her site kendi uploads dizini içinde kendi site haritalarını üretir.

## Geliştirme

```bash
composer install   # Bağımlılıkları kur
composer lint      # PHP söz dizimi kontrolü
vendor/bin/phpcs --standard=phpcs.xml.dist   # WordPress kod standartları
```

## Versiyon Geçmişi

### 2.0.1

- Etiket listesi 5 sınırına indirildi. `bing sitemap` çıkarıldı, böylece `seo` etiketi WordPress.org tarafından yok sayılmak yerine geçerli oldu. `google sitemap` ve `yandex sitemap` korundu, arama motoru kapsamı daralmadı.
- İşlevsel değişiklik yok.

### 2.0.0

**Kritik düzeltmeler**

Yayınlanan 1.0.0 sürümü **etkinleştirmede ölümcül hata veriyordu** ve hiç site haritası üretemiyordu.

- **`data_get()` tanımsızdı.** Bu fonksiyon `rappasoft/laravel-helpers` paketinden geliyor ve Laravel'in `illuminate/support` paketine bağımlı. O paket hiç eklenmemişti, dolayısıyla eklenti çalışamıyordu.
- **Etkinleştirmede ölümcül hata:** `strtotime()` ikinci argüman olarak string alıyordu — PHP 8'de `TypeError`.
- **Her yazı kaydında ölümcül hata:** `save_post` kancası 3 argümanla kayıtlıydı ama callback hiç parametre tanımlamıyordu.
- **Sınıflar hiç yüklenmiyordu:** `index.php` yalnızca Composer autoloader'ı çağırıyordu; ana sınıfı hiçbir kod yolu örneklemiyordu.
- **Ayarlar kaydedilemiyordu:** `settings_fields()` hiç kaydedilmemiş bir ayar grubu kullanıyordu.
- Site haritası URL'leri dosya yolu parçalanarak üretiliyordu — yanlış URL'ler oluşuyordu.
- `lastmod` yerel saatteki `post_date` kullanıyordu; artık doğru GMT ISO 8601 değeri kullanılıyor.

**İyileştirmeler**

- Site haritaları `wp-content/uploads/sitemaps/` altına yazılıyor (web kök dizini çoğu sunucuda yazılabilir değil).
- Namespaced PSR-4 yapı (`OptimistHub\SmartSitemap`); eski `includes/` sınıfları ve kullanılmayan XSL dosyaları kaldırıldı.
- Toplu düzenlemede yeniden üretim istek başına bir kez yapılıyor (önceki davranış: her yazı için bir kez).
- 2.000 URL'lik parçalara bölme; her sayfa yüklemesinde yeniden üretim durduruldu.
- Çıktı resmi sitemaps.org XSD şemasına uygun.
- `uninstall.php` ve `composer.json` eklendi.
- WordPress Coding Standards: 0 hata, 0 uyarı.

### 1.0.01

- Eklenti GitHub adresi değiştirildi.

### 1.0.0

- Kararlı sürüm.

## Bağlantılar

- [WordPress Eklenti Dizini](https://wordpress.org/plugins/smart-sitemap-generator/)
- [SVN Commit Geçmişi](https://plugins.trac.wordpress.org/log/smart-sitemap-generator/)
- [Destek Forumu](https://wordpress.org/support/plugin/smart-sitemap-generator/)
- [Sorun Bildir](https://github.com/optimisthub/smart-sitemap-generator/issues)

## Lisans

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)

---

Geliştirici: [Optimist Hub](https://optimisthub.com)
