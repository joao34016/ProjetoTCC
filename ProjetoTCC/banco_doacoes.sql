-- -----------------------------------------
-- Patas & Lar — tabela de doações
-- Rode este arquivo UMA VEZ no banco "patas_lar" (phpMyAdmin > SQL)
-- -----------------------------------------

CREATE TABLE IF NOT EXISTS doacao (
  id_doacao   INT NOT NULL AUTO_INCREMENT,
  id_cliente  INT NULL,                                   -- NULL = doação feita por visitante (sem login)
  tipo        VARCHAR(20)  NOT NULL,                      -- Conforto, Brinquedo, Higiene, Outro
  item        VARCHAR(150) NOT NULL,                      -- o que será doado
  status      VARCHAR(20)  NOT NULL DEFAULT 'Aguardando entrega',  -- Aguardando entrega, Recebida, Cancelada
  data_doacao DATETIME     NOT NULL,
  data_status DATETIME     NULL,                          -- quando o status foi alterado
  PRIMARY KEY (id_doacao),
  KEY idx_doacao_cliente (id_cliente),
  KEY idx_doacao_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
