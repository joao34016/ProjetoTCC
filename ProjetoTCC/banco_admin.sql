-- Patas & Lar — ajustes de banco para o painel do administrador
-- Rode TUDO dentro do banco que o site usa (conexao.php => patas_lar).
USE patas_lar;

-- Tabela de administradores
CREATE TABLE IF NOT EXISTS administrador (
  id_admin INT AUTO_INCREMENT PRIMARY KEY,
  nome     VARCHAR(100) NOT NULL,
  email    VARCHAR(100) NOT NULL UNIQUE,
  senha    VARCHAR(255) NOT NULL
);

-- Cria o admin padrão só se ainda não existir.
-- (a senha 'adm123' em texto puro é aceita no 1º login e convertida para hash automaticamente)
INSERT INTO administrador (nome, email, senha)
SELECT 'Administrador', 'admin@pataselar.com', 'adm123' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM administrador WHERE email = 'admin@pataselar.com');

-- Tabela de solicitações de adoção (no seu script ela só foi criada em banco_novo)
CREATE TABLE IF NOT EXISTS adocao (
  id_adocao              INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente             INT NOT NULL,
  id_animal              INT NOT NULL,
  data_solicitacao       DATETIME NOT NULL,
  status                 VARCHAR(20) NOT NULL DEFAULT 'Pendente',
  nome_completo          VARCHAR(150) NOT NULL,
  idade                  INT NOT NULL,
  telefone               VARCHAR(30) NOT NULL,
  email                  VARCHAR(150) NOT NULL,
  cidade                 VARCHAR(100) NOT NULL,
  profissao              VARCHAR(100) NOT NULL,
  tipo_residencia        VARCHAR(20) NOT NULL,
  imovel                 VARCHAR(20) NOT NULL,
  espaco_adequado        VARCHAR(20) NOT NULL,
  cercado                VARCHAR(20) NOT NULL,
  condicoes_financeiras  VARCHAR(20) NOT NULL,
  despesas_inesperadas   VARCHAR(20) NOT NULL,
  responsavel_cuidados   VARCHAR(30) NOT NULL,
  horas_sozinho          VARCHAR(30) NOT NULL,
  tempo_disponivel       VARCHAR(20) NOT NULL,
  ja_teve_animais        VARCHAR(20) NOT NULL,
  possui_outros_animais  VARCHAR(20) NOT NULL,
  quais_animais          VARCHAR(255) NULL,
  moradores_de_acordo    VARCHAR(20) NOT NULL,
  motivo                 TEXT NOT NULL,
  plano_dificuldades     TEXT NOT NULL,
  CONSTRAINT fk_adocao_cliente FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente),
  CONSTRAINT fk_adocao_animal  FOREIGN KEY (id_animal)  REFERENCES animal(id_animal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Colunas usadas por animais.php / index.php.
-- Se der erro "Duplicate column name", é só ignorar: já existem.
ALTER TABLE animal ADD COLUMN adotado TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE animal ADD COLUMN data_adocao DATETIME NULL;
