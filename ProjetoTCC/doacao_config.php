<?php
// -----------------------------------------
// Patas & Lar — dados das doações
// Tipos de doação e dados do ponto de coleta.
// (DADOS FICTÍCIOS: para trocar, edite só aqui)
// -----------------------------------------

$tiposDoacao = array(
    "Conforto"  => "🛏️",
    "Brinquedo" => "🧸",
    "Higiene"   => "🧼",
    "Outro"     => "🎁"
);

$statusDoacao = array("Aguardando entrega", "Recebida", "Cancelada");

// classe visual do selo de status (reaproveita as de solicitacoes.css)
$classeStatusDoacao = array(
    "Aguardando entrega" => "pendente",
    "Recebida"           => "aprovada",
    "Cancelada"          => "recusada"
);

$pontoNome        = "Ponto de Coleta Patas & Lar";
$pontoEndereco    = "Rua dos Bichinhos, 123 — Centro, Criciúma/SC · CEP 88800-000";
$pontoReferencia  = "Ao lado da praça central, fachada verde e amarela";
$pontoHorario     = array(
    "Segunda a sexta" => "9h às 17h",
    "Sábado"          => "9h às 12h"
);
$pontoWhatsExibir = "(48) 9903-7681";
$pontoWhatsLink   = "554899037681"; // só números, com 55 (Brasil) na frente

// cartão "Como entregar" (usado na confirmação e em "Minhas doações")
if (!function_exists('cartaoPontoColeta')) {
    function cartaoPontoColeta($mensagemWhats) {
        global $pontoNome, $pontoEndereco, $pontoReferencia, $pontoHorario, $pontoWhatsExibir, $pontoWhatsLink;

        $h  = '<div class="ponto-card">';
        $h .= '<h3>📍 Como entregar</h3>';
        $h .= '<p class="ponto-intro">Leve sua doação até o nosso ponto de coleta, dentro do horário de recebimento.</p>';
        $h .= '<dl class="ponto-lista">';
        $h .= '<div><dt>Local</dt><dd>' . htmlspecialchars($pontoNome) . '<br>' . htmlspecialchars($pontoEndereco)
            . '<br><small>' . htmlspecialchars($pontoReferencia) . '</small></dd></div>';
        $h .= '<div><dt>Horário de recebimento</dt><dd>';
        foreach ($pontoHorario as $dias => $horas) {
            $h .= htmlspecialchars($dias) . ': ' . htmlspecialchars($horas) . '<br>';
        }
        $h .= '</dd></div>';
        $h .= '<div><dt>Contato (WhatsApp)</dt><dd>' . htmlspecialchars($pontoWhatsExibir) . '</dd></div>';
        $h .= '</dl>';
        $h .= '<a class="paw-btn secondary ponto-whats" target="_blank" href="https://wa.me/' . $pontoWhatsLink
            . '?text=' . rawurlencode($mensagemWhats) . '">'
            . '<span class="paw-btn-icon">💬</span>'
            . '<span class="paw-btn-text"><strong>Combinar pelo WhatsApp</strong><small>Abrir conversa com mensagem pronta</small></span></a>';
        $h .= '</div>';
        return $h;
    }
}
