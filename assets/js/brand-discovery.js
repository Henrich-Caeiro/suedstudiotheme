/**
 * SUED Studio — Brand Discovery Interactive Script
 * Handles multi-step navigation, interactive selectors, live ranges,
 * REST API submission, and TXT/JSON file exports.
 */

(function () {
  'use strict';

  const CONFIG = window.SUED_BD_CONFIG || {
    restUrl: '/wp-json/sued/v1/brand-discovery-submit',
    nonce: '',
    siteUrl: '/',
    agencyName: 'SUED Studio',
  };

  const SECS = [
    'Início',
    'O negócio',
    'Alma da marca',
    'Público-alvo',
    'Personalidade visual',
    'Referências',
    'Tom de voz',
    'Contexto final'
  ];

  let cur = 0;

  function updateProgress() {
    const bar = document.getElementById('progressBar');
    if (!bar) return;

    if (cur === 0) {
      bar.style.display = 'none';
      return;
    }

    bar.style.display = 'block';
    const inds = document.getElementById('stepIndicators');
    if (inds) {
      inds.innerHTML = '';
      for (let i = 1; i <= 7; i++) {
        const d = document.createElement('div');
        d.className = 'sued-bd-step-ind' + (i < cur ? ' done' : i === cur ? ' active' : '');
        inds.appendChild(d);
      }
    }

    const secName = document.getElementById('sectionName');
    if (secName) secName.textContent = SECS[cur] || '';

    const progText = document.getElementById('progressText');
    if (progText) progText.textContent = cur + ' de 7';
  }

  function validateStep(n) {
    clearErrors();

    if (n === 0) {
      const name = v('clientName').trim();
      const email = v('clientEmail').trim();
      const company = v('companyName').trim();

      if (!name) {
        return markError('clientName', 'Por favor, informe seu nome.');
      }
      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        return markError('clientEmail', 'Por favor, informe um e-mail válido.');
      }
      if (!company) {
        return markError('companyName', 'Por favor, informe o nome da sua empresa ou projeto.');
      }
    } else if (n === 1) {
      if (!v('elevatorPitch').trim()) {
        return markError('elevatorPitch', 'Por favor, descreva em uma frase o que sua empresa faz.');
      }
      if (!v('bizDetail').trim()) {
        return markError('bizDetail', 'Por favor, detalhe produto, serviço e processo.');
      }
      if (!v('problemSolve').trim()) {
        return markError('problemSolve', 'Por favor, informe o problema central resolvido.');
      }
    } else if (n === 2) {
      if (!v('purpose').trim()) {
        return markError('purpose', 'Por favor, defina o propósito da empresa.');
      }
      if (!v('brandPersonality').trim()) {
        return markError('brandPersonality', 'Por favor, descreva a personalidade da marca.');
      }
      const vals = vvals('valuesGrid');
      if (vals.length < 1) {
        alert('Por favor, selecione ao menos 1 valor inegociável.');
        return false;
      }
      const archList = archs();
      if (archList.length < 1) {
        alert('Por favor, selecione 1 ou 2 arquétipos de marca.');
        return false;
      }
    } else if (n === 3) {
      if (!v('idealClient').trim()) {
        return markError('idealClient', 'Por favor, descreva seu cliente ideal.');
      }
      if (!v('clientPains').trim()) {
        return markError('clientPains', 'Por favor, informe as principais dores do cliente.');
      }
    } else if (n === 4) {
      const adjsList = adjs();
      if (adjsList.length < 1) {
        alert('Por favor, selecione ao menos 1 adjetivo visual.');
        return false;
      }
    } else if (n === 5) {
      const adm = tarr('admired');
      if (adm.length < 1) {
        return markError('admiredList', 'Por favor, cite ao menos 1 marca que você admira.');
      }
    } else if (n === 6) {
      const toneList = tones();
      if (toneList.length < 1) {
        alert('Por favor, selecione ao menos 1 estilo de tom de voz.');
        return false;
      }
      if (!v('voiceYes').trim()) {
        return markError('voiceYes', 'Por favor, escreva uma frase que represente a voz da marca.');
      }
    } else if (n === 7) {
      if (!v('oneMessage').trim()) {
        return markError('oneMessage', 'Por favor, deixe sua mensagem essencial para a equipe criativa.');
      }
      const consent = document.getElementById('lgpdConsent');
      if (!consent || !consent.checked) {
        return markError('lgpdConsent', 'É necessário concordar com o tratamento dos dados para enviar o formulário.');
      }
    }

    return true;
  }

  function markError(id, msg) {
    const el = document.getElementById(id);
    if (el) {
      el.focus();
      el.style.borderColor = '#FF4D4D';
      el.style.boxShadow = '0 0 0 3px rgba(255, 77, 77, 0.25)';
    }
    const errBox = document.getElementById('bdErrorBox');
    if (errBox) {
      errBox.textContent = msg;
      errBox.style.display = 'block';
    }
    return false;
  }

  function clearErrors() {
    document.querySelectorAll('input, textarea').forEach(el => {
      el.style.borderColor = '';
      el.style.boxShadow = '';
    });
    const errBox = document.getElementById('bdErrorBox');
    if (errBox) {
      errBox.textContent = '';
      errBox.style.display = 'none';
    }
  }

  function goTo(n) {
    if (n > cur) {
      if (!validateStep(cur)) return;
    }

    const currentSec = document.getElementById('sec' + cur);
    if (currentSec) currentSec.classList.remove('active');

    cur = n;

    const nextSec = document.getElementById('sec' + n);
    if (nextSec) nextSec.classList.add('active');

    updateProgress();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function initChips(id, single) {
    const el = document.getElementById(id);
    if (!el) return;
    el.querySelectorAll('.sued-bd-chip').forEach(c => {
      c.addEventListener('click', () => {
        if (single) {
          el.querySelectorAll('.sued-bd-chip.sel').forEach(x => x.classList.remove('sel'));
        }
        c.classList.toggle('sel');
      });
    });
  }

  function initVals() {
    document.querySelectorAll('#valuesGrid .sued-bd-val-item').forEach(item => {
      item.addEventListener('click', () => {
        const sel = document.querySelectorAll('#valuesGrid .sued-bd-val-item.sel');
        if (item.classList.contains('sel')) {
          item.classList.remove('sel');
        } else if (sel.length < 6) {
          item.classList.add('sel');
        }
      });
    });
  }

  function initArchs() {
    document.querySelectorAll('#archetypes .sued-bd-arch-card').forEach(c => {
      c.addEventListener('click', () => {
        const sel = document.querySelectorAll('#archetypes .sued-bd-arch-card.sel');
        if (c.classList.contains('sel')) {
          c.classList.remove('sel');
        } else if (sel.length < 2) {
          c.classList.add('sel');
        }
      });
    });
  }

  function initAdj() {
    document.querySelectorAll('#adjGrid .sued-bd-adj-item').forEach(item => {
      item.addEventListener('click', () => {
        const sel = document.querySelectorAll('#adjGrid .sued-bd-adj-item.on');
        if (item.classList.contains('on')) {
          item.classList.remove('on');
        } else if (sel.length < 6) {
          item.classList.add('on');
        }
      });
    });
  }

  function initColors() {
    document.querySelectorAll('#colorRow .sued-bd-color-swatch').forEach(s => {
      s.addEventListener('click', () => {
        const sel = document.querySelectorAll('#colorRow .sued-bd-color-swatch.on');
        if (s.classList.contains('on')) {
          s.classList.remove('on');
        } else if (sel.length < 4) {
          s.classList.add('on');
        }
      });
    });
  }

  function initTones() {
    document.querySelectorAll('#toneGrid .sued-bd-tone-btn').forEach(b => {
      b.addEventListener('click', () => b.classList.toggle('on'));
    });
  }

  function addSingle(listId, cls, ph) {
    const list = document.getElementById(listId);
    if (!list) return;
    const row = document.createElement('div');
    row.className = 'sued-bd-single-row';
    const inp = document.createElement('input');
    inp.type = 'text';
    inp.className = cls;
    inp.placeholder = ph;
    row.appendChild(inp);
    list.appendChild(row);
    inp.focus();
  }

  function chips(id) {
    return Array.from(document.querySelectorAll('#' + id + ' .sued-bd-chip.sel')).map(c => c.dataset.v || c.textContent.trim());
  }

  function vvals(id) {
    return Array.from(document.querySelectorAll('#' + id + ' .sued-bd-val-item.sel')).map(c => c.dataset.v);
  }

  function archs() {
    return Array.from(document.querySelectorAll('#archetypes .sued-bd-arch-card.sel')).map(c => c.dataset.v);
  }

  function adjs() {
    return Array.from(document.querySelectorAll('#adjGrid .sued-bd-adj-item.on')).map(c => c.dataset.v);
  }

  function colors() {
    return Array.from(document.querySelectorAll('#colorRow .sued-bd-color-swatch.on')).map(c => c.dataset.c);
  }

  function tones() {
    return Array.from(document.querySelectorAll('#toneGrid .sued-bd-tone-btn.on')).map(c => c.dataset.v);
  }

  function tarr(cls) {
    return Array.from(document.querySelectorAll('.' + cls)).map(i => i.value.trim()).filter(Boolean);
  }

  function v(id) {
    const el = document.getElementById(id);
    return el ? el.value : '';
  }

  function collect() {
    return {
      meta: {
        exportedAt: new Date().toISOString(),
        form: 'Brand Discovery — SUED Studio',
      },
      identificacao: {
        nomeCliente: v('clientName'),
        email: v('clientEmail'),
        nomeEmpresa: v('companyName'),
        setor: v('sector'),
        tipoProjeto: chips('projectType'),
      },
      negocio: {
        elevatorPitch: v('elevatorPitch'),
        descricaoCompleta: v('bizDetail'),
        problemaSolucionado: v('problemSolve'),
        diferencial: v('differentiator'),
        modeloNegocio: chips('bizModel'),
        estagio: v('bizStage'),
      },
      essencia: {
        proposito: v('purpose'),
        personalidadeMarca: v('brandPersonality'),
        sentimentoCentral: v('brandFeeling'),
        valores: vvals('valuesGrid'),
        arquetipos: archs(),
      },
      publico: {
        clienteIdeal: v('idealClient'),
        dores: v('clientPains'),
        aspiracoes: v('clientAspirations'),
        gatilhoDecisao: v('whyChoose'),
        antiCliente: v('antiClient'),
        concorrentes: tarr('competitor'),
      },
      personalidadeVisual: {
        espectros: {
          minimalistaVsExuberante: v('sp1'),
          classicoVsModerno: v('sp2'),
          serioVsDescontraido: v('sp3'),
          acessivelVsPremium: v('sp4'),
          frioVsQuente: v('sp5'),
          localVsGlobal: v('sp6'),
        },
        adjetivosPresentes: adjs(),
        adjetivosAusentes: v('adjNot'),
        coresSelecionadas: colors(),
        notasCores: v('colorNotes'),
      },
      referencias: {
        marcasAdmiradas: tarr('admired'),
        marcasNaoQueridas: tarr('disliked'),
        simboloMetafora: v('symbolIdea'),
        marcaComoLugar: v('brandPlace'),
        marcaComoMusica: v('brandMusic'),
        marcaComoMaterial: v('brandTexture'),
      },
      tomDeVoz: {
        estilos: tones(),
        fraseSimEVoz: v('voiceYes'),
        fraseNaoEVoz: v('voiceNo'),
        pessoa: chips('voicePerson'),
        aplicacoesPrioritarias: chips('applications'),
      },
      contextualFinal: {
        mensagemParaDesigner: v('oneMessage'),
        historiaDaMarca: v('brandStory'),
        medoSobreAMarca: v('brandFear'),
        visaoDeFuturo: v('futureVision'),
        informacaoAdicional: v('extra'),
      },
    };
  }

  async function submitForm() {
    if (!validateStep(7)) return;

    const btn = document.getElementById('btnSubmitBD');
    const btnText = btn ? btn.querySelector('.btn-text') : null;
    const btnLoader = btn ? btn.querySelector('.btn-loader') : null;
    const errBox = document.getElementById('bdErrorBox');

    if (btn) btn.disabled = true;
    if (btnText) btnText.style.display = 'none';
    if (btnLoader) btnLoader.style.display = 'inline-flex';
    if (errBox) errBox.style.display = 'none';

    const payload = collect();
    payload.sued_hp_check = v('sued_hp_check');
    payload.website = v('website');
    payload.source = window.location.href;

    try {
      const res = await fetch(CONFIG.restUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': CONFIG.nonce || '',
        },
        body: JSON.stringify(payload),
      });

      const json = await res.json();

      if (res.ok && json.success) {
        const sec7 = document.getElementById('sec7');
        if (sec7) sec7.classList.remove('active');

        const successScreen = document.getElementById('successScreen');
        if (successScreen) successScreen.classList.add('active');

        const progressBar = document.getElementById('progressBar');
        if (progressBar) progressBar.style.display = 'none';

        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        throw new Error(json.message || 'Erro ao enviar respostas. Verifique os dados e tente novamente.');
      }
    } catch (err) {
      if (errBox) {
        errBox.textContent = err.message || 'Erro de conexão. Tente novamente.';
        errBox.style.display = 'block';
      }
      if (btn) btn.disabled = false;
      if (btnText) btnText.style.display = 'inline-flex';
      if (btnLoader) btnLoader.style.display = 'none';
    }
  }

  function dl(content, filename, type) {
    const blob = new Blob([content], { type });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
  }

  function slug() {
    return (v('companyName') || 'cliente')
      .toLowerCase()
      .replace(/\s+/g, '-')
      .replace(/[^a-z0-9-]/g, '');
  }

  function exportJSON() {
    dl(JSON.stringify(collect(), null, 2), 'brand-discovery-' + slug() + '.json', 'application/json');
  }

  function exportTXT() {
    const d = collect();
    const L = [];
    L.push('BRAND DISCOVERY — SUED STUDIO');
    L.push('Empresa / Projeto: ' + (d.identificacao.nomeEmpresa || '').toUpperCase());
    L.push('Exportado em: ' + new Date().toLocaleString('pt-BR'));
    L.push('='.repeat(65));

    const s = t => {
      L.push('');
      L.push(t.toUpperCase());
      L.push('-'.repeat(45));
    };

    const r = (label, val) => {
      if (val && (Array.isArray(val) ? val.length : val)) {
        L.push(label + ': ' + (Array.isArray(val) ? val.join(', ') : val));
      }
    };

    s('1. Identificação');
    r('Nome do Cliente', d.identificacao.nomeCliente);
    r('E-mail', d.identificacao.email);
    r('Empresa / Projeto', d.identificacao.nomeEmpresa);
    r('Setor de Atuação', d.identificacao.setor);
    r('Tipo de Projeto', d.identificacao.tipoProjeto);

    s('2. O Negócio');
    r('Elevator Pitch', d.negocio.elevatorPitch);
    r('Descrição Completa', d.negocio.descricaoCompleta);
    r('Problema Central que Resolve', d.negocio.problemaSolucionado);
    r('Diferencial Competitivo', d.negocio.diferencial);
    r('Modelo de Negócio', d.negocio.modeloNegocio);
    r('Estágio Atual', d.negocio.estagio);

    s('3. Alma da Marca & Arquétipos');
    r('Propósito Essencial', d.essencia.proposito);
    r('Personalidade como Pessoa', d.essencia.personalidadeMarca);
    r('Sentimento a Despertar', d.essencia.sentimentoCentral);
    r('Valores Inegociáveis', d.essencia.valores);
    r('Arquétipos Selecionados', d.essencia.arquetipos);

    s('4. Público-Alvo & Mercado');
    r('Cliente Ideal', d.publico.clienteIdeal);
    r('Dores e Frustrações', d.publico.dores);
    r('Aspirações', d.publico.aspiracoes);
    r('Por que escolheriam você', d.publico.gatilhoDecisao);
    r('Anti-cliente (quem NÃO é)', d.publico.antiCliente);
    r('Concorrentes', d.publico.concorrentes);

    s('5. Personalidade Visual');
    const e = d.personalidadeVisual.espectros;
    L.push('Espectros de Posicionamento (1 a 10):');
    L.push('  • Minimalista vs Exuberante: ' + e.minimalistaVsExuberante + '/10');
    L.push('  • Clássico/Atemporal vs Moderno/Futurista: ' + e.classicoVsModerno + '/10');
    L.push('  • Sério/Corporativo vs Descontraído/Irreverente: ' + e.serioVsDescontraido + '/10');
    L.push('  • Acessível/Popular vs Premium/Exclusivo: ' + e.acessivelVsPremium + '/10');
    L.push('  • Frio/Técnico vs Quente/Humano: ' + e.frioVsQuente + '/10');
    L.push('  • Local/Regional vs Global/Internacional: ' + e.localVsGlobal + '/10');
    r('Adjetivos Presentes (SIM)', d.personalidadeVisual.adjetivosPresentes);
    r('Adjetivos Ausentes (NÃO)', d.personalidadeVisual.adjetivosAusentes);
    r('Cores Selecionadas', d.personalidadeVisual.coresSelecionadas);
    r('Notas de Cores', d.personalidadeVisual.notasCores);

    s('6. Referências & Associações Criativas');
    r('Marcas que Admira', d.referencias.marcasAdmiradas);
    r('Marcas que Repele', d.referencias.marcasNaoQueridas);
    r('Símbolo / Metáfora', d.referencias.simboloMetafora);
    r('Se fosse um Lugar', d.referencias.marcaComoLugar);
    r('Se fosse Música', d.referencias.marcaComoMusica);
    r('Textura / Material', d.referencias.marcaComoMaterial);

    s('7. Tom de Voz & Aplicações');
    r('Estilos de Tom', d.tomDeVoz.estilos);
    r('Frase SIM (Voz da Marca)', d.tomDeVoz.fraseSimEVoz);
    r('Frase NÃO (Jamais diria)', d.tomDeVoz.fraseNaoEVoz);
    r('Pessoa Verbal', d.tomDeVoz.pessoa);
    r('Aplicações Prioritárias', d.tomDeVoz.aplicacoesPrioritarias);

    s('8. Contexto Final & Mensagem ao Designer');
    r('Mensagem Essencial', d.contextualFinal.mensagemParaDesigner);
    r('História da Marca', d.contextualFinal.historiaDaMarca);
    r('O que seria um Fracasso (Medo)', d.contextualFinal.medoSobreAMarca);
    r('Visão em 3–5 Anos', d.contextualFinal.visaoDeFuturo);
    r('Informações Adicionais', d.contextualFinal.informacaoAdicional);

    L.push('');
    L.push('='.repeat(65));
    L.push('SUED Studio — Strategic Growth Partner');

    dl(L.join('\n'), 'brand-discovery-' + slug() + '.txt', 'text/plain;charset=utf-8');
  }

  // Expose to global namespace
  window.suedBD = {
    goTo,
    addSingle,
    submitForm,
    exportJSON,
    exportTXT,
  };

  document.addEventListener('DOMContentLoaded', () => {
    initChips('projectType', true);
    initChips('bizModel', false);
    initVals();
    initArchs();
    initAdj();
    initColors();
    initTones();
    initChips('voicePerson', true);
    initChips('applications', false);
    updateProgress();
  });
})();

