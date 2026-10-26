-- Patas & Lar — tabela de administradores + ajustes necessários
-- Execute no phpMyAdmin, no banco patas_lar, aba "SQL".
-- Se algum comando der erro dizendo que a coluna/tabela já existe, pode ignorar e seguir para o próximo.

-- 1) tabela de administradores — login separado da tabela "cliente"
CREATE TABLE IF NOT EXISTS administrador (
  id_admin  INT AUTO_INCREMENT PRIMARY KEY,
  nome      VARCHAR(100) NOT NULL,
  email     VARCHAR(100) NOT NULL UNIQUE,
  senha     VARCHAR(255) NOT NULL
);

-- 2) colunas que o site usa para marcar um animal como adotado
--    (pule estes dois comandos se a sua tabela "animal" já tiver essas colunas)
ALTER TABLE animal ADD COLUMN adotado TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE animal ADD COLUMN data_adocao DATETIME NULL;

-- 3) liga as solicitações de adoção ao cliente e ao animal correspondentes
ALTER TABLE adocao ADD CONSTRAINT fk_adocao_cliente FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente);
ALTER TABLE adocao ADD CONSTRAINT fk_adocao_animal  FOREIGN KEY (id_animal)  REFERENCES animal(id_animal);
