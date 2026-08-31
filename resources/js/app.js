// TipStop Network — اسکریپت‌های سمت کلاینت

// ------------------------------------------------------------
// انیمیشن ورود عناصر هنگام اسکرول (Scroll Reveal)
// ------------------------------------------------------------
(function () {
    var els = document.querySelectorAll('[data-reveal]');

    if (!('IntersectionObserver' in window)) {
        els.forEach(function (el) { el.classList.add('revealed'); });

        return;
    }

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    els.forEach(function (el) { io.observe(el); });
})();

// ------------------------------------------------------------
// شمارنده انیمیشنی اعداد (data-counter)
// ------------------------------------------------------------
(function () {
    var counters = document.querySelectorAll('[data-counter]');

    if (!counters.length) return;

    var animate = function (el) {
        var target = parseFloat(el.getAttribute('data-counter')) || 0;
        var suffix = el.getAttribute('data-counter-suffix') || '';
        var decimals = parseInt(el.getAttribute('data-counter-decimal') || '0', 10);
        var duration = 1400;
        var start = null;

        var step = function (ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / duration, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            var value = target * eased;

            el.textContent = (decimals > 0
                ? value.toFixed(decimals)
                : Math.round(value).toLocaleString('en-US')) + suffix;

            if (p < 1) requestAnimationFrame(step);
        };

        requestAnimationFrame(step);
    };

    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animate(entry.target);
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.4 });

        counters.forEach(function (el) { io.observe(el); });
    } else {
        counters.forEach(animate);
    }
})();

// ------------------------------------------------------------
// افکت تیلت سه‌بعدی روی کارت‌ها (data-tilt)
// ------------------------------------------------------------
(function () {
    if (window.matchMedia('(pointer: coarse)').matches) return;

    var active = null;

    document.addEventListener('mousemove', function (e) {
        var card = e.target.closest('[data-tilt]');

        // بازنشانی کارت قبلی هنگام خروج
        if (active && active !== card) {
            active.style.transform = '';
            active = null;
        }

        if (!card) return;

        active = card;

        var rect = card.getBoundingClientRect();
        var x = (e.clientX - rect.left) / rect.width - 0.5;
        var y = (e.clientY - rect.top) / rect.height - 0.5;

        card.style.transform = 'perspective(900px) rotateX(' + (-y * 6).toFixed(2) + 'deg) rotateY(' + (x * 6).toFixed(2) + 'deg) translateY(-4px)';
    });
})();

// ------------------------------------------------------------
// افکت درخشش دنبال‌کننده ماوس روی کارت‌ها (data-spotlight)
// ------------------------------------------------------------
document.addEventListener('mousemove', function (e) {
    var card = e.target.closest('[data-spotlight]');

    if (!card) return;

    var rect = card.getBoundingClientRect();

    card.style.setProperty('--spot-x', ((e.clientX - rect.left) / rect.width * 100) + '%');
    card.style.setProperty('--spot-y', ((e.clientY - rect.top) / rect.height * 100) + '%');
});

// ------------------------------------------------------------
// تغییر ظاهر روشن/تاریک
// ------------------------------------------------------------
function toggleTheme() {
    var d = document.documentElement;

    d.classList.toggle('dark');

    try { localStorage.setItem('theme', d.classList.contains('dark') ? 'dark' : 'light'); } catch (e) {}
}

// ------------------------------------------------------------
// دکمه‌های کپی
// ------------------------------------------------------------
document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-copy]');

    if (!btn) return;

    navigator.clipboard.writeText(btn.getAttribute('data-copy')).then(function () {
        var old = btn.textContent;

        btn.textContent = '✓';
        btn.classList.add('!text-emerald-500');

        setTimeout(function () {
            btn.textContent = old;
            btn.classList.remove('!text-emerald-500');
        }, 1500);
    });
});

// ------------------------------------------------------------
// منوی موبایل
// ------------------------------------------------------------
function toggleMobileMenu() {
    var menu = document.getElementById('mobile-menu');
    var burger = document.getElementById('burger');

    if (!menu) return;

    var isOpen = menu.classList.toggle('open');

    if (burger) {
        burger.querySelector('.burger-l1')?.classList.toggle('rotate-45', isOpen);
        burger.querySelector('.burger-l1')?.classList.toggle('translate-y-[7px]', isOpen);
        burger.querySelector('.burger-l2')?.classList.toggle('opacity-0', isOpen);
        burger.querySelector('.burger-l3')?.classList.toggle('-rotate-45', isOpen);
        burger.querySelector('.burger-l3')?.classList.toggle('-translate-y-[7px]', isOpen);
    }
}

// ------------------------------------------------------------
// پیام‌های فلش (Toast)
// ------------------------------------------------------------
(function () {
    document.querySelectorAll('[data-flash]').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .5s, transform .5s';
            el.style.opacity = '0';
            el.style.transform = 'translate(-50%, -16px)';
            setTimeout(function () { el.remove(); }, 500);
        }, 6000);
    });
})();
