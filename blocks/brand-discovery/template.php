<?php
/**
 * Dynamic block template for sued-studio/brand-discovery
 *
 * @package SUED_Studio
 */

defined('ABSPATH') || exit;

// Enqueue Tabler Icons for form glyphs
wp_enqueue_style(
    'tabler-icons',
    'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css',
    [],
    '2.47.0'
);

// Enqueue Brand Discovery CSS & JS
wp_enqueue_style('sued-brand-discovery-css', SUED_URI . '/assets/css/brand-discovery.css', ['tabler-icons'], SUED_VERSION);
wp_enqueue_script('sued-brand-discovery-js',  SUED_URI . '/assets/js/brand-discovery.js', [], SUED_VERSION, ['in_footer' => true]);

wp_localize_script('sued-brand-discovery-js', 'SUED_BD_CONFIG', [
    'restUrl'    => esc_url_raw(rest_url('sued/v1/brand-discovery-submit')),
    'nonce'      => wp_create_nonce('wp_rest'),
    'siteUrl'    => home_url('/'),
    'agencyName' => 'SUED Studio',
]);
?>
<div <?php echo get_block_wrapper_attributes(['class' => 'sued-brand-discovery-section']); ?>>
  <div class="sued-bd-wrapper">
    <div class="sued-bd-glow" aria-hidden="true"></div>

    <!-- HERO -->
    <div class="sued-bd-hero">
      <div class="sued-bd-hero-eyebrow">
        <i class="ti ti-brand-abstract"></i> Brand Discovery
      </div>
      <h1>Vamos criar algo<br><em>verdadeiramente seu</em></h1>
      <p>Este formulário é o ponto de partida de tudo. Quanto mais honesto e detalhado você for, mais forte e autêntica será a sua marca.</p>
      <div class="sued-bd-hero-meta">
        <span><i class="ti ti-clock"></i> 15–20 minutos</span>
        <span><i class="ti ti-list-check"></i> 7 etapas guiadas</span>
        <span><i class="ti ti-lock"></i> Respostas estritamente confidenciais</span>
      </div>
    </div>

    <!-- STICKY PROGRESS BAR -->
    <div class="sued-bd-progress-bar" id="progressBar">
      <div class="sued-bd-progress-inner">
        <div class="sued-bd-step-indicators" id="stepIndicators"></div>
        <div class="sued-bd-progress-section-name" id="sectionName"></div>
        <div class="sued-bd-progress-text" id="progressText"></div>
      </div>
    </div>

    <!-- MAIN FORM CONTAINER -->
    <div class="sued-bd-container">

      <!-- ETAPA 0: INTRODUÇÃO & IDENTIFICAÇÃO -->
      <div class="sued-bd-section active" id="sec0">
        <div class="sued-bd-section-header center">
          <h2>Antes de começar,<br>conte um pouco sobre o projeto</h2>
          <p>Três informações rápidas para personalizar as perguntas à sua realidade.</p>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">Identificação</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label">Qual é a natureza deste projeto? <span class="req">*</span></label>
            <div class="sued-bd-chips" id="projectType">
              <div class="sued-bd-chip" data-v="Nova marca"><i class="ti ti-sparkles"></i> Nova marca</div>
              <div class="sued-bd-chip" data-v="Rebranding"><i class="ti ti-refresh"></i> Rebranding</div>
              <div class="sued-bd-chip" data-v="Refresh visual"><i class="ti ti-brush"></i> Refresh visual</div>
              <div class="sued-bd-chip" data-v="Sub-marca"><i class="ti ti-layers-subtract"></i> Sub-marca</div>
              <div class="sued-bd-chip" data-v="Marca pessoal"><i class="ti ti-user-circle"></i> Marca pessoal</div>
            </div>
          </div>
          <hr class="sued-bd-divider">
          <div class="sued-bd-two-col">
            <div class="sued-bd-field">
              <label class="sued-bd-field-label" for="clientName">Seu nome <span class="req">*</span></label>
              <input type="text" id="clientName" placeholder="Como podemos te chamar?">
            </div>
            <div class="sued-bd-field">
              <label class="sued-bd-field-label" for="clientEmail">Seu melhor e-mail <span class="req">*</span></label>
              <input type="email" id="clientEmail" placeholder="seu@email.com">
            </div>
          </div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="companyName">Nome da empresa ou projeto <span class="req">*</span></label>
            <input type="text" id="companyName" placeholder="Nome atual ou provisório — tudo bem se ainda não estiver definido">
          </div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="sector">Setor / segmento de atuação</label>
            <input type="text" id="sector" placeholder="Ex: tecnologia, saúde, moda, gastronomia, consultoria, educação...">
          </div>
        </div>
        <div class="sued-bd-nav right">
          <button type="button" class="sued-btn-next" onclick="suedBD.goTo(1)">Começar <i class="ti ti-arrow-right"></i></button>
        </div>
      </div>

      <!-- ETAPA 1: O NEGÓCIO -->
      <div class="sued-bd-section" id="sec1">
        <div class="sued-bd-section-header">
          <div class="sued-bd-step-label">Etapa 1 de 7</div>
          <h2>O negócio</h2>
          <p>Queremos entender o que você faz, para quem faz e por que alguém pagaria por isso.</p>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">O essencial</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="elevatorPitch">Em uma frase — o que a sua empresa faz? <span class="req">*</span></label>
            <p class="sued-bd-field-hint">Imagine que você tem 10 segundos no elevador. Seja direto e concreto.</p>
            <input type="text" id="elevatorPitch" placeholder="Ex: Ajudamos clínicas a lotar a agenda sem precisar de secretária">
          </div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="bizDetail">O que você faz, para quem e como? <span class="req">*</span></label>
            <textarea id="bizDetail" placeholder="Fale sobre produto/serviço, processo e perfil dos clientes..."></textarea>
          </div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="problemSolve">Qual é o problema central que você resolve? <span class="req">*</span></label>
            <p class="sued-bd-field-hint">Não o que você vende — o que muda na vida da pessoa depois que ela te contrata.</p>
            <textarea id="problemSolve" placeholder="Ex: Nossos clientes param de perder 3 horas por dia com planilhas..."></textarea>
          </div>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">Contexto</div>
          <div class="sued-bd-two-col">
            <div class="sued-bd-field">
              <label class="sued-bd-field-label">Modelo de negócio</label>
              <div class="sued-bd-chips vertical" id="bizModel">
                <div class="sued-bd-chip" data-v="B2B — vende para empresas">B2B — vende para empresas</div>
                <div class="sued-bd-chip" data-v="B2C — vende para pessoas">B2C — vende para pessoas</div>
                <div class="sued-bd-chip" data-v="B2B2C — ambos">B2B2C — ambos</div>
                <div class="sued-bd-chip" data-v="SaaS / software">SaaS / software</div>
                <div class="sued-bd-chip" data-v="Marketplace">Marketplace</div>
                <div class="sued-bd-chip" data-v="E-commerce">E-commerce</div>
                <div class="sued-bd-chip" data-v="Varejo físico">Varejo físico</div>
                <div class="sued-bd-chip" data-v="Serviços profissionais">Serviços profissionais</div>
              </div>
            </div>
            <div class="sued-bd-field">
              <label class="sued-bd-field-label" for="bizStage">Estágio atual</label>
              <select id="bizStage">
                <option value="">Selecione...</option>
                <option value="Ideia — ainda não lançou">Ideia — ainda não lançou</option>
                <option value="MVP — validando com primeiros clientes">MVP — validando com primeiros clientes</option>
                <option value="Early stage — crescendo">Early stage — crescendo</option>
                <option value="Scale-up — em expansão">Scale-up — em expansão</option>
                <option value="Empresa estabelecida (5+ anos)">Empresa estabelecida (5+ anos)</option>
                <option value="Corporação / grande empresa">Corporação / grande empresa</option>
              </select>
              <label class="sued-bd-field-label" for="differentiator" style="margin-top:20px;">Seu maior diferencial</label>
              <textarea id="differentiator" style="min-height:75px" placeholder="O que te diferencia da concorrência em 2–3 frases..."></textarea>
            </div>
          </div>
        </div>
        <div class="sued-bd-nav">
          <button type="button" class="sued-btn-back" onclick="suedBD.goTo(0)"><i class="ti ti-arrow-left"></i> Voltar</button>
          <button type="button" class="sued-btn-next" onclick="suedBD.goTo(2)">Próximo <i class="ti ti-arrow-right"></i></button>
        </div>
      </div>

      <!-- ETAPA 2: A ALMA DA MARCA -->
      <div class="sued-bd-section" id="sec2">
        <div class="sued-bd-section-header">
          <div class="sued-bd-step-label">Etapa 2 de 7</div>
          <h2>A alma da marca</h2>
          <p>Por que você existe além do retorno financeiro? As marcas mais duradouras têm uma razão de ser que transcende o produto.</p>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">Propósito e personalidade</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="purpose">Qual é o propósito da sua empresa? <span class="req">*</span></label>
            <p class="sued-bd-field-hint">Por que o mundo seria um pouco pior sem o que você faz?</p>
            <textarea id="purpose" placeholder="Ex: Acreditamos que toda criança, independente de onde nasceu, merece aprender a criar com tecnologia..."></textarea>
          </div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="brandPersonality">Se a sua marca fosse uma pessoa, como ela seria? <span class="req">*</span></label>
            <p class="sued-bd-field-hint">Personalidade, jeito de falar, como se veste, o que lê, como age, o que defende com paixão...</p>
            <textarea id="brandPersonality" placeholder="Pense numa pessoa real ou num personagem que tenha a essência que você imagina..."></textarea>
          </div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="brandFeeling">Que sentimento a marca quer despertar?</label>
            <input type="text" id="brandFeeling" placeholder="Ex: a sensação de que tudo vai ficar bem / empoderamento / pertencimento...">
          </div>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">Valores inegociáveis</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label">Selecione de 4 a 6 valores essenciais <span class="req">*</span></label>
            <p class="sued-bd-field-hint">Coisas de que você jamais abriria mão em qualquer circunstância.</p>
            <div class="sued-bd-values-grid" id="valuesGrid">
              <div class="sued-bd-val-item" data-v="Transparência"><i class="ti ti-eye"></i> Transparência</div>
              <div class="sued-bd-val-item" data-v="Inovação"><i class="ti ti-bulb"></i> Inovação</div>
              <div class="sued-bd-val-item" data-v="Foco nas pessoas"><i class="ti ti-heart"></i> Foco nas pessoas</div>
              <div class="sued-bd-val-item" data-v="Simplicidade"><i class="ti ti-minimize"></i> Simplicidade</div>
              <div class="sued-bd-val-item" data-v="Excelência"><i class="ti ti-award"></i> Excelência</div>
              <div class="sued-bd-val-item" data-v="Ousadia"><i class="ti ti-bolt"></i> Ousadia</div>
              <div class="sued-bd-val-item" data-v="Cuidado"><i class="ti ti-leaf"></i> Cuidado</div>
              <div class="sued-bd-val-item" data-v="Responsabilidade"><i class="ti ti-shield"></i> Responsabilidade</div>
              <div class="sued-bd-val-item" data-v="Criatividade"><i class="ti ti-palette"></i> Criatividade</div>
              <div class="sued-bd-val-item" data-v="Autenticidade"><i class="ti ti-fingerprint"></i> Autenticidade</div>
              <div class="sued-bd-val-item" data-v="Sustentabilidade"><i class="ti ti-plant"></i> Sustentabilidade</div>
              <div class="sued-bd-val-item" data-v="Comunidade"><i class="ti ti-users"></i> Comunidade</div>
              <div class="sued-bd-val-item" data-v="Liberdade"><i class="ti ti-feather"></i> Liberdade</div>
              <div class="sued-bd-val-item" data-v="Rigor técnico"><i class="ti ti-ruler"></i> Rigor técnico</div>
              <div class="sued-bd-val-item" data-v="Diversidade"><i class="ti ti-world"></i> Diversidade</div>
              <div class="sued-bd-val-item" data-v="Legado"><i class="ti ti-building"></i> Legado</div>
            </div>
          </div>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">Arquétipos de Marca</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label">Qual arquétipo representa melhor sua marca? <span class="req">*</span></label>
            <p class="sued-bd-field-hint">Selecione 1 ou 2 arquétipos que guiarão o tom de voz e a linguagem visual.</p>
            <div class="sued-bd-arch-grid" id="archetypes">
              <div class="sued-bd-arch-card" data-v="O Herói">
                <div class="sued-bd-arch-name">O Herói</div>
                <div class="sued-bd-arch-sub">Inspira, supera desafios, busca a vitória</div>
                <div class="sued-bd-arch-ref">Nike, Adidas, FedEx</div>
              </div>
              <div class="sued-bd-arch-card" data-v="O Sábio">
                <div class="sued-bd-arch-name">O Sábio</div>
                <div class="sued-bd-arch-sub">Conhecimento, verdade, confiança intelectual</div>
                <div class="sued-bd-arch-ref">Google, TED, BBC</div>
              </div>
              <div class="sued-bd-arch-card" data-v="O Criador">
                <div class="sued-bd-arch-name">O Criador</div>
                <div class="sued-bd-arch-sub">Inovação, expressão, visão pioneira</div>
                <div class="sued-bd-arch-ref">Apple, Lego, Adobe</div>
              </div>
              <div class="sued-bd-arch-card" data-v="O Cuidador">
                <div class="sued-bd-arch-name">O Cuidador</div>
                <div class="sued-bd-arch-sub">Empatia, proteção, serviço ao próximo</div>
                <div class="sued-bd-arch-ref">Johnson's, Dove</div>
              </div>
              <div class="sued-bd-arch-card" data-v="O Rebelde">
                <div class="sued-bd-arch-name">O Rebelde</div>
                <div class="sued-bd-arch-sub">Ruptura, liberdade, autenticidade radical</div>
                <div class="sued-bd-arch-ref">Harley-Davidson, Diesel</div>
              </div>
              <div class="sued-bd-arch-card" data-v="O Explorador">
                <div class="sued-bd-arch-name">O Explorador</div>
                <div class="sued-bd-arch-sub">Aventura, descoberta, autenticidade</div>
                <div class="sued-bd-arch-ref">Patagonia, Jeep, RedBull</div>
              </div>
              <div class="sued-bd-arch-card" data-v="O Mago">
                <div class="sued-bd-arch-name">O Mago</div>
                <div class="sued-bd-arch-sub">Transformação, visão, possibilidade</div>
                <div class="sued-bd-arch-ref">Disney, Tesla, Dyson</div>
              </div>
              <div class="sued-bd-arch-card" data-v="O Governante">
                <div class="sued-bd-arch-name">O Governante</div>
                <div class="sued-bd-arch-sub">Liderança, controle, prestígio</div>
                <div class="sued-bd-arch-ref">Mercedes, Rolex</div>
              </div>
              <div class="sued-bd-arch-card" data-v="O Amante">
                <div class="sued-bd-arch-name">O Amante</div>
                <div class="sued-bd-arch-sub">Beleza, conexão profunda, sofisticação</div>
                <div class="sued-bd-arch-ref">Chanel, Häagen-Dazs</div>
              </div>
              <div class="sued-bd-arch-card" data-v="O Bobo da Corte">
                <div class="sued-bd-arch-name">O Bobo da Corte</div>
                <div class="sued-bd-arch-sub">Diversão, leveza, acessibilidade</div>
                <div class="sued-bd-arch-ref">M&Ms, Old Spice, Fanta</div>
              </div>
              <div class="sued-bd-arch-card" data-v="O Homem Comum">
                <div class="sued-bd-arch-name">O Homem Comum</div>
                <div class="sued-bd-arch-sub">Pertencimento, confiança, proximidade</div>
                <div class="sued-bd-arch-ref">IKEA, eBay, GAP</div>
              </div>
              <div class="sued-bd-arch-card" data-v="O Inocente">
                <div class="sued-bd-arch-name">O Inocente</div>
                <div class="sued-bd-arch-sub">Otimismo, pureza, simplicidade</div>
                <div class="sued-bd-arch-ref">Coca-Cola, Aveeno</div>
              </div>
            </div>
          </div>
        </div>
        <div class="sued-bd-nav">
          <button type="button" class="sued-btn-back" onclick="suedBD.goTo(1)"><i class="ti ti-arrow-left"></i> Voltar</button>
          <button type="button" class="sued-btn-next" onclick="suedBD.goTo(3)">Próximo <i class="ti ti-arrow-right"></i></button>
        </div>
      </div>

      <!-- ETAPA 3: PÚBLICO & MERCADO -->
      <div class="sued-bd-section" id="sec3">
        <div class="sued-bd-section-header">
          <div class="sued-bd-step-label">Etapa 3 de 7</div>
          <h2>Para quem você fala</h2>
          <p>Uma marca que tenta agradar todo mundo não agrada ninguém. Quanto mais específico, mais poderoso.</p>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">O cliente ideal</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="idealClient">Descreva seu cliente ideal com riqueza de detalhes <span class="req">*</span></label>
            <p class="sued-bd-field-hint">Dê um nome fictício. Fale sobre idade, profissão, rotina, sonhos e frustrações.</p>
            <textarea id="idealClient" style="min-height:105px" placeholder="Ex: Paulo, 42 anos, dono de farmácia no interior. Trabalha 12h por dia, tem 2 filhos no colégio particular..."></textarea>
          </div>
          <div class="sued-bd-two-col">
            <div class="sued-bd-field">
              <label class="sued-bd-field-label" for="clientPains">Principais dores e frustrações <span class="req">*</span></label>
              <textarea id="clientPains" placeholder="O que tira o sono deles? O que já tentaram antes e não funcionou?"></textarea>
            </div>
            <div class="sued-bd-field">
              <label class="sued-bd-field-label" for="clientAspirations">Maiores aspirações e desejos</label>
              <textarea id="clientAspirations" placeholder="O que querem conquistar? Como seria a vida deles no cenário ideal?"></textarea>
            </div>
          </div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="whyChoose">Por que escolheriam você — e não a concorrência?</label>
            <textarea id="whyChoose" style="min-height:75px" placeholder="O gatilho de decisão — o que os convence na hora H?"></textarea>
          </div>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">Mercado e limites</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="antiClient">Quem NÃO é seu cliente?</label>
            <p class="sued-bd-field-hint">Definir quem você não quer atender é tão estratégico quanto o oposto.</p>
            <textarea id="antiClient" style="min-height:75px" placeholder="Ex: Não é para quem busca o menor preço. Não é para empresas sem processos mínimos..."></textarea>
          </div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label">Principais concorrentes</label>
            <p class="sued-bd-field-hint">Inclua concorrentes indiretos — quem seu cliente compara com você?</p>
            <div id="competitorList">
              <div class="sued-bd-single-row"><input type="text" class="competitor" placeholder="Concorrente 1 — o que ele faz bem e onde é fraco"></div>
              <div class="sued-bd-single-row"><input type="text" class="competitor" placeholder="Concorrente 2"></div>
              <div class="sued-bd-single-row"><input type="text" class="competitor" placeholder="Concorrente 3"></div>
            </div>
            <button type="button" class="sued-bd-btn-add-row" onclick="suedBD.addSingle('competitorList','competitor','Mais um concorrente')">+ Adicionar concorrente</button>
          </div>
        </div>
        <div class="sued-bd-nav">
          <button type="button" class="sued-btn-back" onclick="suedBD.goTo(2)"><i class="ti ti-arrow-left"></i> Voltar</button>
          <button type="button" class="sued-btn-next" onclick="suedBD.goTo(4)">Próximo <i class="ti ti-arrow-right"></i></button>
        </div>
      </div>

      <!-- ETAPA 4: PERSONALIDADE VISUAL -->
      <div class="sued-bd-section" id="sec4">
        <div class="sued-bd-section-header">
          <div class="sued-bd-step-label">Etapa 4 de 7</div>
          <h2>Personalidade visual</h2>
          <p>Ainda não é o design final — é a direção estratégica. Estamos calibrando a bússola estética.</p>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">Espectros de posicionamento</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label">Onde sua marca se posiciona em cada eixo? <span class="req">*</span></label>
            <p class="sued-bd-field-hint">Deslize para a ponta que melhor representa a intenção visual.</p>
            <div id="spectrums">
              <div class="sued-bd-spectrum-row">
                <div class="sued-bd-spectrum-labels"><span>Minimalista</span><span>Exuberante</span></div>
                <input type="range" min="1" max="10" value="5" id="sp1" oninput="document.getElementById('sv1').textContent=this.value">
                <div class="sued-bd-spectrum-val" id="sv1">5</div>
              </div>
              <div class="sued-bd-spectrum-row">
                <div class="sued-bd-spectrum-labels"><span>Clássico / Atemporal</span><span>Moderno / Futurista</span></div>
                <input type="range" min="1" max="10" value="5" id="sp2" oninput="document.getElementById('sv2').textContent=this.value">
                <div class="sued-bd-spectrum-val" id="sv2">5</div>
              </div>
              <div class="sued-bd-spectrum-row">
                <div class="sued-bd-spectrum-labels"><span>Sério / Corporativo</span><span>Descontraído / Irreverente</span></div>
                <input type="range" min="1" max="10" value="5" id="sp3" oninput="document.getElementById('sv3').textContent=this.value">
                <div class="sued-bd-spectrum-val" id="sv3">5</div>
              </div>
              <div class="sued-bd-spectrum-row">
                <div class="sued-bd-spectrum-labels"><span>Acessível / Popular</span><span>Premium / Exclusivo</span></div>
                <input type="range" min="1" max="10" value="5" id="sp4" oninput="document.getElementById('sv4').textContent=this.value">
                <div class="sued-bd-spectrum-val" id="sv4">5</div>
              </div>
              <div class="sued-bd-spectrum-row">
                <div class="sued-bd-spectrum-labels"><span>Frio / Técnico</span><span>Quente / Humano</span></div>
                <input type="range" min="1" max="10" value="5" id="sp5" oninput="document.getElementById('sv5').textContent=this.value">
                <div class="sued-bd-spectrum-val" id="sv5">5</div>
              </div>
              <div class="sued-bd-spectrum-row">
                <div class="sued-bd-spectrum-labels"><span>Local / Regional</span><span>Global / Internacional</span></div>
                <input type="range" min="1" max="10" value="5" id="sp6" oninput="document.getElementById('sv6').textContent=this.value">
                <div class="sued-bd-spectrum-val" id="sv6">5</div>
              </div>
            </div>
          </div>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">Adjetivos visuais</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label">O que DEVE estar presente na identidade <span class="req">*</span></label>
            <p class="sued-bd-field-hint">Escolha até 6 diretrizes de design essenciais.</p>
            <div class="sued-bd-adj-grid" id="adjGrid">
              <div class="sued-bd-adj-item" data-v="Bold"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Bold</span></div>
              <div class="sued-bd-adj-item" data-v="Delicado"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Delicado</span></div>
              <div class="sued-bd-adj-item" data-v="Orgânico"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Orgânico</span></div>
              <div class="sued-bd-adj-item" data-v="Geométrico"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Geométrico</span></div>
              <div class="sued-bd-adj-item" data-v="Artesanal"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Artesanal</span></div>
              <div class="sued-bd-adj-item" data-v="Tecnológico"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Tecnológico</span></div>
              <div class="sued-bd-adj-item" data-v="Luxuoso"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Luxuoso</span></div>
              <div class="sued-bd-adj-item" data-v="Lúdico"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Lúdico</span></div>
              <div class="sued-bd-adj-item" data-v="Sóbrio"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Sóbrio</span></div>
              <div class="sued-bd-adj-item" data-v="Vibrante"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Vibrante</span></div>
              <div class="sued-bd-adj-item" data-v="Raw / Bruto"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Raw / Bruto</span></div>
              <div class="sued-bd-adj-item" data-v="Refinado"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Refinado</span></div>
              <div class="sued-bd-adj-item" data-v="Expressivo"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Expressivo</span></div>
              <div class="sued-bd-adj-item" data-v="Limpo"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Limpo</span></div>
              <div class="sued-bd-adj-item" data-v="Texturizado"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Texturizado</span></div>
              <div class="sued-bd-adj-item" data-v="Editorial"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Editorial</span></div>
              <div class="sued-bd-adj-item" data-v="Dramático"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Dramático</span></div>
              <div class="sued-bd-adj-item" data-v="Sutil"><div class="sued-bd-adj-check"><i class="ti ti-check"></i></div><span>Sutil</span></div>
            </div>
          </div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="adjNot">O que NÃO deve estar presente</label>
            <textarea id="adjNot" style="min-height:75px" placeholder="Ex: nada infantil, nada burocrático, sem clichês óbvios do setor..."></textarea>
          </div>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">Cores e paleta</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label">Cores que te atraem para esta marca</label>
            <p class="sued-bd-field-hint">Selecione até 4 tonalidades.</p>
            <div class="sued-bd-color-row" id="colorRow">
              <div class="sued-bd-color-swatch" style="background:#0F1923;" data-c="Preto / Navy Escuro" title="Preto / Navy Escuro"></div>
              <div class="sued-bd-color-swatch" style="background:#F5F2EC; border:1px solid #7A8B95;" data-c="Off-white / Creme" title="Off-white / Creme"></div>
              <div class="sued-bd-color-swatch" style="background:#26AFFF;" data-c="Azul Elétrico (SUED)" title="Azul Elétrico"></div>
              <div class="sued-bd-color-swatch" style="background:#C8A96E;" data-c="Dourado / Champagne (SUED)" title="Dourado / Champagne"></div>
              <div class="sued-bd-color-swatch" style="background:#1A3A5C;" data-c="Azul Profundo" title="Azul Profundo"></div>
              <div class="sued-bd-color-swatch" style="background:#2A6049;" data-c="Verde Floresta" title="Verde Floresta"></div>
              <div class="sued-bd-color-swatch" style="background:#C8401A;" data-c="Terracota / Vermelho Queimado" title="Terracota"></div>
              <div class="sued-bd-color-swatch" style="background:#F5A623;" data-c="Âmbar / Laranja" title="Âmbar"></div>
              <div class="sued-bd-color-swatch" style="background:#7B2D8B;" data-c="Vinho / Roxo" title="Vinho / Roxo"></div>
              <div class="sued-bd-color-swatch" style="background:#1ABC9C;" data-c="Teal / Verde-água" title="Teal"></div>
              <div class="sued-bd-color-swatch" style="background:#D4A5A5;" data-c="Rosa Antigo / Blush" title="Blush"></div>
              <div class="sued-bd-color-swatch" style="background:#C0A882;" data-c="Areia / Nude" title="Areia / Nude"></div>
              <div class="sued-bd-color-swatch" style="background:#E74C3C;" data-c="Vermelho Vibrante" title="Vermelho Vibrante"></div>
              <div class="sued-bd-color-swatch" style="background:#2C3E50;" data-c="Chumbo / Grafite" title="Chumbo"></div>
            </div>
          </div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="colorNotes">Existe alguma cor obrigatória ou proibida?</label>
            <textarea id="colorNotes" style="min-height:65px" placeholder="Ex: o verde é da concorrente direta / o azul precisa permanecer do logo antigo..."></textarea>
          </div>
        </div>
        <div class="sued-bd-nav">
          <button type="button" class="sued-btn-back" onclick="suedBD.goTo(3)"><i class="ti ti-arrow-left"></i> Voltar</button>
          <button type="button" class="sued-btn-next" onclick="suedBD.goTo(5)">Próximo <i class="ti ti-arrow-right"></i></button>
        </div>
      </div>

      <!-- ETAPA 5: REFERÊNCIAS -->
      <div class="sued-bd-section" id="sec5">
        <div class="sued-bd-section-header">
          <div class="sued-bd-step-label">Etapa 5 de 7</div>
          <h2>Referências e inspirações</h2>
          <p>O que você admira revela seu gosto. O que detesta revela seus limites. Ambos são igualmente valiosos.</p>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">O que inspira</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label">Marcas que você ADMIRA visualmente <span class="req">*</span></label>
            <p class="sued-bd-field-hint">Preferencialmente fora do seu setor. O que especificamente te atrai em cada uma?</p>
            <div id="admiredList">
              <div class="sued-bd-single-row"><input type="text" class="admired" placeholder="Nome da marca — o que admira nela (cor, forma, tipografia, sensação...)"></div>
              <div class="sued-bd-single-row"><input type="text" class="admired" placeholder="Nome da marca — o que admira nela"></div>
              <div class="sued-bd-single-row"><input type="text" class="admired" placeholder="Nome da marca — o que admira nela"></div>
            </div>
            <button type="button" class="sued-bd-btn-add-row" onclick="suedBD.addSingle('admiredList','admired','Nome da marca — o que admira nela')">+ Adicionar referência</button>
          </div>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">O que repele</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label">Marcas que você NÃO quer se parecer</label>
            <div id="dislikedList">
              <div class="sued-bd-single-row"><input type="text" class="disliked" placeholder="Nome da marca — por que não quer se parecer"></div>
              <div class="sued-bd-single-row"><input type="text" class="disliked" placeholder="Nome da marca — por que não quer se parecer"></div>
            </div>
            <button type="button" class="sued-bd-btn-add-row" onclick="suedBD.addSingle('dislikedList','disliked','Nome da marca — por que não quer se parecer')">+ Adicionar</button>
          </div>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">Associações criativas</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="symbolIdea">Existe algum símbolo, objeto ou metáfora ligada ao negócio?</label>
            <textarea id="symbolIdea" style="min-height:75px" placeholder="Ex: a bússola representa orientação... / o diamante bruto representa o potencial escondido..."></textarea>
          </div>
          <div class="sued-bd-two-col">
            <div class="sued-bd-field">
              <label class="sued-bd-field-label" for="brandPlace">Se fosse um lugar, qual seria?</label>
              <textarea id="brandPlace" style="min-height:75px" placeholder="Uma biblioteca antiga? Um estúdio moderno em Berlim? Um observatório?"></textarea>
            </div>
            <div class="sued-bd-field">
              <label class="sued-bd-field-label" for="brandMusic">Se fosse uma música ou gênero?</label>
              <textarea id="brandMusic" style="min-height:75px" placeholder="Jazz clássico? Eletrônico minimalista? Post-rock cinematográfico?"></textarea>
            </div>
          </div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="brandTexture">Se fosse um material ou textura?</label>
            <input type="text" id="brandTexture" placeholder="Ex: concreto escovado, linho nobre, seda italiana, titânio anodizado...">
          </div>
        </div>
        <div class="sued-bd-nav">
          <button type="button" class="sued-btn-back" onclick="suedBD.goTo(4)"><i class="ti ti-arrow-left"></i> Voltar</button>
          <button type="button" class="sued-btn-next" onclick="suedBD.goTo(6)">Próximo <i class="ti ti-arrow-right"></i></button>
        </div>
      </div>

      <!-- ETAPA 6: TOM DE VOZ -->
      <div class="sued-bd-section" id="sec6">
        <div class="sued-bd-section-header">
          <div class="sued-bd-step-label">Etapa 6 de 7</div>
          <h2>Como sua marca fala</h2>
          <p>Tom de voz é tão parte da identidade quanto o logotipo. Uma frase com o tom errado desfaz uma percepção visual impecável.</p>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">Estilo de comunicação</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label">Como é o jeito da sua marca falar? <span class="req">*</span></label>
            <div class="sued-bd-tone-grid" id="toneGrid">
              <div class="sued-bd-tone-btn" data-v="Formal"><div class="sued-bd-tone-title">Formal</div><div class="sued-bd-tone-sub">Técnico, distante, institucional</div></div>
              <div class="sued-bd-tone-btn" data-v="Informal e próximo"><div class="sued-bd-tone-title">Informal e próximo</div><div class="sued-bd-tone-sub">Conversa amigável, leveza, proximidade</div></div>
              <div class="sued-bd-tone-btn" data-v="Especialista / técnico"><div class="sued-bd-tone-title">Especialista / técnico</div><div class="sued-bd-tone-sub">Autoridade, precisão, profundidade</div></div>
              <div class="sued-bd-tone-btn" data-v="Simples e direto"><div class="sued-bd-tone-title">Simples e direto</div><div class="sued-bd-tone-sub">Objetividade, clareza, sem jargões</div></div>
              <div class="sued-bd-tone-btn" data-v="Inspirador"><div class="sued-bd-tone-title">Inspirador</div><div class="sued-bd-tone-sub">Motivação, visão ampla, energia positiva</div></div>
              <div class="sued-bd-tone-btn" data-v="Divertido"><div class="sued-bd-tone-title">Divertido</div><div class="sued-bd-tone-sub">Humor sutil, descontraído, cativante</div></div>
              <div class="sued-bd-tone-btn" data-v="Sério e firme"><div class="sued-bd-tone-title">Sério e firme</div><div class="sued-bd-tone-sub">Responsabilidade, solidez, credibilidade</div></div>
              <div class="sued-bd-tone-btn" data-v="Empático e acolhedor"><div class="sued-bd-tone-title">Empático e acolhedor</div><div class="sued-bd-tone-sub">Escuta ativa, humanidade, acolhimento</div></div>
              <div class="sued-bd-tone-btn" data-v="Ousado / Provocador"><div class="sued-bd-tone-title">Ousado / Provocador</div><div class="sued-bd-tone-sub">Questionador, sem filtros, disruptivo</div></div>
              <div class="sued-bd-tone-btn" data-v="Poético / Literário"><div class="sued-bd-tone-title">Poético / Literário</div><div class="sued-bd-tone-sub">Metáforas, narrativa rica, elegância verbal</div></div>
            </div>
          </div>
          <div class="sued-bd-two-col">
            <div class="sued-bd-field">
              <label class="sued-bd-field-label" for="voiceYes">Frase que SIM é a voz da marca <span class="req">*</span></label>
              <textarea id="voiceYes" placeholder="Escreva como esta marca falaria de verdade..."></textarea>
            </div>
            <div class="sued-bd-field">
              <label class="sued-bd-field-label" for="voiceNo">Frase que NÃO é a voz da marca</label>
              <textarea id="voiceNo" placeholder="Uma frase que esta marca jamais diria..."></textarea>
            </div>
          </div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label">A marca fala na 1ª ou 3ª pessoa?</label>
            <div class="sued-bd-chips" id="voicePerson">
              <div class="sued-bd-chip" data-v="Nós"><i class="ti ti-users"></i> "Nós acreditamos..." / "Nossa missão..."</div>
              <div class="sued-bd-chip" data-v="3ª pessoa"><i class="ti ti-building"></i> "A [Marca] acredita..." / "A empresa..."</div>
              <div class="sued-bd-chip" data-v="Eu"><i class="ti ti-user"></i> "Eu sou..." (marca pessoal)</div>
            </div>
          </div>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">Aplicações prioritárias</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label">Onde a marca vai aparecer primeiro?</label>
            <div class="sued-bd-chips" id="applications">
              <div class="sued-bd-chip" data-v="Logotipo e variações"><i class="ti ti-photo"></i> Logotipo e variações</div>
              <div class="sued-bd-chip" data-v="Site / landing page"><i class="ti ti-world"></i> Site / landing page</div>
              <div class="sued-bd-chip" data-v="Redes sociais"><i class="ti ti-brand-instagram"></i> Redes sociais</div>
              <div class="sued-bd-chip" data-v="Papelaria"><i class="ti ti-notes"></i> Papelaria</div>
              <div class="sued-bd-chip" data-v="Embalagem"><i class="ti ti-package"></i> Embalagem</div>
              <div class="sued-bd-chip" data-v="App / interface digital"><i class="ti ti-device-mobile"></i> App / interface digital</div>
              <div class="sued-bd-chip" data-v="Apresentações"><i class="ti ti-presentation"></i> Apresentações</div>
              <div class="sued-bd-chip" data-v="Sinalização física"><i class="ti ti-map-pin"></i> Sinalização física</div>
              <div class="sued-bd-chip" data-v="Uniforme / vestuário"><i class="ti ti-shirt"></i> Uniforme / vestuário</div>
              <div class="sued-bd-chip" data-v="Mídia paga"><i class="ti ti-ad"></i> Mídia paga</div>
            </div>
          </div>
        </div>
        <div class="sued-bd-nav">
          <button type="button" class="sued-btn-back" onclick="suedBD.goTo(5)"><i class="ti ti-arrow-left"></i> Voltar</button>
          <button type="button" class="sued-btn-next" onclick="suedBD.goTo(7)">Próximo <i class="ti ti-arrow-right"></i></button>
        </div>
      </div>

      <!-- ETAPA 7: CONTEXTO FINAL -->
      <div class="sued-bd-section" id="sec7">
        <div class="sued-bd-section-header">
          <div class="sued-bd-step-label">Etapa 7 de 7</div>
          <h2>As perguntas que revelam tudo</h2>
          <p>Respostas abertas e sinceras. Não existe resposta errada — existe a sua verdade.</p>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">O essencial</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="oneMessage">Se pudesse dizer uma única coisa para os designers antes de começarem, o que diria? <span class="req">*</span></label>
            <p class="sued-bd-field-hint">Pode ser um sentimento, uma preocupação, uma esperança ou um aviso.</p>
            <textarea id="oneMessage" style="min-height:105px" placeholder="Ex: Quero que as pessoas sintam que podem confiar cegamente nessa marca desde o primeiro olhar..."></textarea>
          </div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="brandStory">Existe alguma história marcante que capture a essência do negócio?</label>
            <p class="sued-bd-field-hint">Uma boa história vale mais que mil briefings. Pode ser um episódio do início ou uma conquista recente.</p>
            <textarea id="brandStory" style="min-height:115px" placeholder="Fique à vontade para contar o que quiser..."></textarea>
          </div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="brandFear">O que faria essa marca ser um fracasso? O que você definitivamente NÃO quer?</label>
            <textarea id="brandFear" placeholder="Ex: Parecer mais uma fintech genérica. Soar presunçosa. Se afastar das minhas origens..."></textarea>
          </div>
        </div>
        <div class="sued-bd-card">
          <div class="sued-bd-card-title">Visão de futuro e fechamento</div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="futureVision">Em 3 a 5 anos, se tudo correr bem, como será a empresa?</label>
            <textarea id="futureVision" placeholder="Tamanho, mercados atendidos, novos produtos, reputação no setor..."></textarea>
          </div>
          <div class="sued-bd-field">
            <label class="sued-bd-field-label" for="extra">Informações adicionais</label>
            <textarea id="extra" placeholder="Links para referências, restrições contratuais, arquivos complementares ou histórico..."></textarea>
          </div>

          <!-- Honeypot anti-spam: NO name attributes to prevent autofill triggering spam filter -->
          <div style="display:none;" aria-hidden="true">
            <input type="text" id="sued_hp_check" tabindex="-1" autocomplete="new-password" aria-hidden="true">
            <input type="text" id="website" tabindex="-1" autocomplete="new-password" aria-hidden="true">
          </div>

          <!-- LGPD Consent -->
          <label class="sued-bd-consent">
            <input type="checkbox" id="lgpdConsent" checked>
            <span>Concordo com o tratamento confidencial dos dados deste briefing pela equipe estratégica da SUED Studio, em conformidade com as diretrizes da LGPD.</span>
          </label>
        </div>

        <div class="sued-bd-error-box" id="bdErrorBox" style="display:none;"></div>

        <div class="sued-bd-nav">
          <button type="button" class="sued-btn-back" onclick="suedBD.goTo(6)"><i class="ti ti-arrow-left"></i> Voltar</button>
          <button type="button" class="sued-btn-submit" id="btnSubmitBD" onclick="suedBD.submitForm()">
            <span class="btn-text"><i class="ti ti-check"></i> Enviar Brand Discovery</span>
            <span class="btn-loader" style="display:none;"><i class="ti ti-loader-2 ti-spin"></i> Enviando...</span>
          </button>
        </div>
      </div>

      <!-- TELA DE SUCESSO -->
      <div class="sued-bd-success-screen" id="successScreen">
        <div class="sued-bd-success-icon"><i class="ti ti-confetti"></i></div>
        <h2>Recebido com perfeição.</h2>
        <p>Suas respostas nos deram tudo o que precisávamos para começar a construir algo verdadeiramente seu. O próximo passo é nosso.</p>
        
        <div class="sued-bd-export-row">
          <button type="button" class="sued-btn-export" onclick="suedBD.exportJSON()">
            <i class="ti ti-download"></i> Baixar respostas (JSON)
          </button>
          <button type="button" class="sued-btn-export secondary" onclick="suedBD.exportTXT()">
            <i class="ti ti-file-text"></i> Versão legível (TXT)
          </button>
        </div>

        <div style="margin-top:36px; padding-top:28px; border-top:1px solid #2A3A47;">
          <a href="<?php echo esc_url(home_url('/')); ?>" class="sued-btn-secondary-link">
            &larr; Voltar para a Página Inicial
          </a>
        </div>
      </div>

    </div>

    <!-- FOOTER DA PÁGINA -->
    <div class="sued-bd-footer">
      Formulário <strong>Brand Discovery</strong> — desenvolvido pela <strong>SUED Studio</strong> para garantir que cada marca criada seja única, potente e autêntica.
    </div>

  </div>
</div>

