/* ═══════════════════════════════════════════
   INTRO — logo animada (roda uma vez por sessão)
═══════════════════════════════════════════ */
(function () {
    const intro = document.getElementById('intro');
    const video = document.getElementById('introVideo');
    if (!intro || !video) return;

    // ── Ajuste fino: quantos segundos ANTES do fim o fade começa ──
    const CUT_BEFORE_END = 0.9; // ← mude este valor se precisar afinar

    function dismiss() {
        intro.classList.add('is-hidden');
        intro.addEventListener('transitionend', () => intro.remove(), { once: true });
    }

    // Já viu nesta sessão — some imediatamente
    if (sessionStorage.getItem('introSeen')) {
        intro.remove();
        return;
    }

    document.body.style.overflow = 'hidden';

    function onEnd() {
        sessionStorage.setItem('introSeen', '1');
        document.body.style.overflow = '';
        dismiss();
    }

    // Fallback de segurança (máx 7s)
    let fallback = setTimeout(onEnd, 7000);

    function clearAndEnd() {
        clearTimeout(fallback);
        onEnd();
    }

    video.addEventListener('loadedmetadata', () => {
        // Calcula o ponto de corte: duração real − CUT_BEFORE_END
        const cutAt = Math.max(0.1, video.duration - CUT_BEFORE_END);

        // Monitora o tempo e dispara o fade no ponto certo
        function checkTime() {
            if (video.currentTime >= cutAt) {
                video.removeEventListener('timeupdate', checkTime);
                clearAndEnd();
            }
        }
        video.addEventListener('timeupdate', checkTime);

        // Garante que o fallback cubra a duração real
        clearTimeout(fallback);
        fallback = setTimeout(onEnd, (video.duration + 1) * 1000);
    });

    video.play().catch(() => {
        clearTimeout(fallback);
        dismiss();
        document.body.style.overflow = '';
    });

    // Clique/tap pula a intro
    intro.addEventListener('click', clearAndEnd, { once: true });
}());


/* ═══════════════════════════════════════════
   HEADER — scroll behavior
═══════════════════════════════════════════ */
const header = document.getElementById('header');

const updateHeader = () => {
    header.classList.toggle('header--scrolled', window.scrollY > 40);
};

window.addEventListener('scroll', updateHeader, { passive: true });
updateHeader();


/* ═══════════════════════════════════════════
   MENU MOBILE
═══════════════════════════════════════════ */
const menuToggle = document.getElementById('menuToggle');
const nav = document.getElementById('nav');

menuToggle.addEventListener('click', () => {
    const isOpen = nav.classList.toggle('nav--open');
    menuToggle.classList.toggle('is-open', isOpen);
    menuToggle.setAttribute('aria-expanded', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
});

// Fecha ao clicar num link
nav.querySelectorAll('.nav__link').forEach(link => {
    link.addEventListener('click', () => {
        nav.classList.remove('nav--open');
        menuToggle.classList.remove('is-open');
        menuToggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    });
});


/* ═══════════════════════════════════════════
   SEÇÃO PROBLEMA — ciclo automático
═══════════════════════════════════════════ */
(function () {
    const DURATION = 4500; // ms por item

    const items = document.querySelectorAll('.problema__item');
    const dots  = document.querySelectorAll('.problema__dot');

    if (!items.length) return;

    let current   = 0;
    let timer     = null;
    let startTime = null;
    let rafId     = null;

    function activate(idx) {
        // Remove estado ativo de todos
        items.forEach(el => {
            el.classList.remove('is-active');
            const bar = el.querySelector('.problema__progress span');
            if (bar) { bar.style.transition = 'none'; bar.style.width = '0%'; }
        });
        dots.forEach(el => {
            el.classList.remove('is-active');
            el.setAttribute('aria-selected', 'false');
        });

        // Ativa o atual
        current = idx;
        items[current].classList.add('is-active');
        dots[current].classList.add('is-active');
        dots[current].setAttribute('aria-selected', 'true');

        // Anima a barra de progresso
        const bar = items[current].querySelector('.problema__progress span');
        if (bar) {
            // força reflow para reiniciar a transição
            bar.getBoundingClientRect();
            bar.style.transition = `width ${DURATION}ms linear`;
            bar.style.width = '100%';
        }

        // Agenda próximo
        clearTimeout(timer);
        timer = setTimeout(() => {
            activate((current + 1) % items.length);
        }, DURATION);
    }

    // Clique manual nos dots
    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            activate(parseInt(dot.dataset.idx, 10));
        });
    });

    // Clique manual nos próprios items
    items.forEach(item => {
        item.addEventListener('click', () => {
            activate(parseInt(item.dataset.idx, 10));
        });
    });

    // Pausa ao hover na seção
    const section = document.getElementById('problema');
    if (section) {
        section.addEventListener('mouseenter', () => clearTimeout(timer));
        section.addEventListener('mouseleave', () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                activate((current + 1) % items.length);
            }, DURATION);
        });
    }

    // Inicia
    activate(0);
}());


/* ═══════════════════════════════════════════
   MÉTODO COLUNA LIVRE — troca de foto no scroll
═══════════════════════════════════════════ */
(function () {
    const steps  = document.querySelectorAll('.metodo__step[data-step]');
    const slides = document.querySelectorAll('.metodo__foto-slide[data-slide]');
    const dots   = document.querySelectorAll('.metodo__foto-dot[data-dot]');

    if (!steps.length || !slides.length) return;

    function setActive(idx) {
        // Etapas
        steps.forEach((s, i) => s.classList.toggle('is-active', i === idx));
        // Slides
        slides.forEach((s, i) => {
            const isActive = i === idx;
            s.classList.toggle('is-active', isActive);
            // Controla vídeo se existir
            const vid = s.querySelector('video');
            if (vid) {
                if (isActive) {
                    vid.play().catch(() => {}); // silencia erros de autoplay
                } else {
                    vid.pause();
                    vid.currentTime = 0;
                }
            }
        });
        // Dots
        dots.forEach((d, i) => d.classList.toggle('is-active', i === idx));
    }

    // Activa o primeiro por padrão
    setActive(0);

    // IntersectionObserver: activa quando o step fica no centro do viewport
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const idx = parseInt(entry.target.dataset.step, 10);
                setActive(idx);
            }
        });
    }, {
        rootMargin: '-40% 0px -40% 0px', // activa quando ~centro na tela
        threshold: 0
    });

    steps.forEach(step => obs.observe(step));

    // Clique nos dots também troca
    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            const idx = parseInt(dot.dataset.dot, 10);
            setActive(idx);
            // Scroll suave até o step correspondente
            if (steps[idx]) {
                steps[idx].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });
}());


/* ═══════════════════════════════════════════
   PERGUNTAS FREQUENTES — accordion
═══════════════════════════════════════════ */
(function () {
    document.querySelectorAll('.perguntas__btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const isOpen = btn.getAttribute('aria-expanded') === 'true';
            const resp   = btn.nextElementSibling;

            btn.setAttribute('aria-expanded', !isOpen);
            resp.style.maxHeight = isOpen ? null : resp.scrollHeight + 'px';
        });
    });
}());


/* ═══════════════════════════════════════════
   SCROLL REVEAL — fade-up global
═══════════════════════════════════════════ */
(function () {
    const revealObs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                revealObs.unobserve(entry.target); // anima só uma vez
            }
        });
    }, {
        rootMargin: '0px 0px -12% 0px', // dispara um pouco antes do rodapé
        threshold: 0.1
    });

    document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));
}());


/* ═══════════════════════════════════════════
   STATS — contador animado
═══════════════════════════════════════════ */
(function () {
    const counters = document.querySelectorAll('.stat__num[data-count]');
    if (!counters.length) return;

    const obs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el      = entry.target;
            const target  = parseInt(el.dataset.count, 10);
            const prefix  = el.dataset.prefix  || '';
            const suffix  = el.dataset.suffix  || '';
            const dur     = 1600; // ms
            const t0      = performance.now();

            function tick(now) {
                const p = Math.min((now - t0) / dur, 1);
                // easeOutCubic
                const eased = 1 - Math.pow(1 - p, 3);
                el.textContent = prefix + Math.round(eased * target) + suffix;
                if (p < 1) requestAnimationFrame(tick);
            }

            requestAnimationFrame(tick);
            obs.unobserve(el);
        });
    }, { threshold: 0.6 });

    counters.forEach(el => obs.observe(el));
}());


/* ═══════════════════════════════════════════
   SOBRE — play vídeo de apresentação
═══════════════════════════════════════════ */
(function () {
    const playBtn = document.getElementById('sobrePlay');
    const video   = document.getElementById('sobreVideo');
    const foto    = document.getElementById('sobreFoto');
    if (!playBtn || !video || !foto) return;

    playBtn.addEventListener('click', () => {
        foto.classList.add('is-hidden');
        playBtn.classList.add('is-hidden');
        video.classList.add('is-playing');
        video.play();
    });

    // Ao terminar o vídeo, volta para a foto
    video.addEventListener('ended', () => {
        video.classList.remove('is-playing');
        video.currentTime = 0;
        foto.classList.remove('is-hidden');
        playBtn.classList.remove('is-hidden');
    });
}());

/* ═══════════════════════════════════════════
   DYNAMIC CONTENT LOADING (CMS)
═══════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', async () => {
    try {
        // Inicializa a conexão com o banco de dados local (JSON) e ignora cache
        const res = await fetch('data.json?v=' + new Date().getTime());
        if (!res.ok) throw new Error('data.json not found');
        const data = await res.json();

        // Utilitário de formatação de string (Quebras de Linha)
        const nl2br = (str) => {
            if (typeof str !== 'string') return '';
            return str.replace(/\n/g, '<br>');
        };

        // Injeção de Dados: Componentes da Capa (Hero)
        const heroTitle = document.querySelector('.hero__title');
        if (heroTitle) {
            const h1 = data.hero_title_1 ? nl2br(data.hero_title_1) : '';
            const hDest = data.hero_title_destaque ? '<em>' + nl2br(data.hero_title_destaque) + '</em>' : '';
            const h2 = data.hero_title_2 ? nl2br(data.hero_title_2) : '';
            heroTitle.innerHTML = [h1, hDest, h2].filter(Boolean).join(' ');
        }
        
        const heroSubtitle = document.querySelector('.hero__subtitle');
        if (heroSubtitle && data.hero_subtitle) heroSubtitle.innerHTML = nl2br(data.hero_subtitle);

        const heroImage = document.querySelector('.hero__image');
        if (heroImage && data.hero_bg_path) heroImage.src = data.hero_bg_path;

        // Injeção de Dados: Componentes Biográficos
        const sobreBioElements = document.querySelectorAll('.sobre__bio');
        if (sobreBioElements.length >= 3) {
            if (data.sobre_bio_1) sobreBioElements[0].innerHTML = nl2br(data.sobre_bio_1);
            if (data.sobre_bio_2) sobreBioElements[1].innerHTML = nl2br(data.sobre_bio_2);
            if (data.sobre_bio_3) sobreBioElements[2].innerHTML = nl2br(data.sobre_bio_3);
        }

        const sobreFoto = document.getElementById('sobreFoto');
        if (sobreFoto && data.sobre_foto_path) sobreFoto.src = data.sobre_foto_path;

        // Injeção de Dados: Grid de Avaliações
        const depoimentosGrid = document.querySelector('.depoimentos__grid');
        if (depoimentosGrid && data.depoimentos && Array.isArray(data.depoimentos)) {
            let depoimentosHTML = '';
            data.depoimentos.forEach((dep, index) => {
                const delayClass = `reveal--d${(index % 3) + 1}`; // Rotates d1, d2, d3
                const initial = dep.autor ? dep.autor.charAt(0).toUpperCase() : 'U';
                
                // Construct stars based on number
                let starsHTML = '';
                const starsCount = dep.estrelas || 5;
                for(let i=0; i<starsCount; i++) starsHTML += '★';

                depoimentosHTML += `
                <div class="depoimento__card reveal ${delayClass}">
                    <div class="depoimento__stars" aria-label="${starsCount} estrelas">${starsHTML}</div>
                    <blockquote class="depoimento__texto">"${dep.texto}"</blockquote>
                    <div class="depoimento__autor">
                        <div class="depoimento__avatar" aria-hidden="true">${initial}</div>
                        <div>
                            <p class="depoimento__nome">${dep.autor}</p>
                            <p class="depoimento__detalhe">Avaliação do Google</p>
                        </div>
                    </div>
                </div>`;
            });
            depoimentosGrid.innerHTML = depoimentosHTML;

            // Trigger scroll reveal observer for new elements
            const revealObs = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        revealObs.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '0px 0px -12% 0px', threshold: 0.1 });
            document.querySelectorAll('.depoimentos__grid .reveal').forEach(el => revealObs.observe(el));
        }

        // Injeção de Dados: Globais (WhatsApp)
        if (data.whatsapp_number) {
            const num = data.whatsapp_number.replace(/\D/g, '');
            document.querySelectorAll('a[href*="api.whatsapp.com"]').forEach(link => {
                link.href = `https://api.whatsapp.com/send/?phone=${num}&text&type=phone_number&app_absent=0`;
            });
        }

        // Injeção de Dados: Estatísticas
        const statNums = document.querySelectorAll('.stat__num');
        const statLabels = document.querySelectorAll('.stat__label');
        if (statNums.length >= 3) {
            if (data.stat_1_num) { 
                statNums[0].dataset.count = data.stat_1_num.replace(/\D/g, ''); 
                statNums[0].dataset.prefix = data.stat_1_num.replace(/[0-9]/g, ''); 
            }
            if (data.stat_2_num) { 
                statNums[1].dataset.count = data.stat_2_num.replace(/\D/g, ''); 
                statNums[1].dataset.prefix = data.stat_2_num.replace(/[0-9]/g, ''); 
            }
            if (data.stat_3_num) { 
                statNums[2].dataset.count = data.stat_3_num.replace(/\D/g, ''); 
                statNums[2].dataset.suffix = data.stat_3_num.replace(/[0-9]/g, ''); 
            }
        }
        if (statLabels.length >= 3) {
            if (data.stat_1_text) statLabels[0].textContent = data.stat_1_text;
            if (data.stat_2_text) statLabels[1].textContent = data.stat_2_text;
            if (data.stat_3_text) statLabels[2].textContent = data.stat_3_text;
        }

        // Injeção de Dados: Instagram
        let instaUrls = [];
        if (data.insta_urls && Array.isArray(data.insta_urls)) {
            instaUrls = data.insta_urls;
        } else if (data.insta_url) {
            instaUrls = [data.insta_url];
        }

        if (instaUrls.length > 0) {
            const instaRow = document.querySelector('.insta__embeds-row');
            if (instaRow) {
                // Remove estilos de formatação passados para reativar Grid/Carrossel do CSS
                instaRow.style.display = '';
                instaRow.style.justifyContent = '';
                
                let instaHTML = '';
                instaUrls.forEach(url => {
                    if(url.trim() !== '') {
                        instaHTML += `
                        <div class="insta__embed-wrap">
                            <blockquote class="instagram-media" data-instgrm-permalink="${url}" data-instgrm-version="14"></blockquote>
                        </div>`;
                    }
                });
                
                if(instaHTML !== '') {
                    instaRow.innerHTML = instaHTML;
                    if (window.instgrm) window.instgrm.Embeds.process();
                }
            }
        }

        // Injeção de Dados: FAQ
        const faqList = document.querySelector('.perguntas__lista');
        if (faqList && data.faq && Array.isArray(data.faq) && data.faq.length > 0) {
            let faqHTML = '';
            data.faq.forEach(f => {
                faqHTML += `
                <div class="perguntas__item">
                    <button class="perguntas__btn" aria-expanded="false">
                        <span>${f.pergunta}</span>
                        <svg class="perguntas__chevron" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    </button>
                    <div class="perguntas__resp">
                        <p>${nl2br(f.resposta)}</p>
                    </div>
                </div>`;
            });
            faqList.innerHTML = faqHTML;
            
            // Re-bind click events for newly loaded FAQs
            document.querySelectorAll('.perguntas__item .perguntas__btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const isOpen = btn.getAttribute('aria-expanded') === 'true';
                    const resp = btn.nextElementSibling;
                    btn.setAttribute('aria-expanded', !isOpen);
                    resp.style.maxHeight = isOpen ? null : resp.scrollHeight + 'px';
                });
            });
        }

    } catch (err) {
        console.error('Failed to load dynamic content:', err);
    }
});
