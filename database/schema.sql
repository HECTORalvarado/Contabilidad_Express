-- Esquema PostgreSQL 15+ para Contabilidad Express, S.A. de C.V.

CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(40) NOT NULL UNIQUE
);

CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol_id INTEGER NOT NULL REFERENCES roles(id),
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    intentos_fallidos SMALLINT NOT NULL DEFAULT 0,
    bloqueado_hasta TIMESTAMPTZ NULL,
    creado_en TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE terceros (
    id SERIAL PRIMARY KEY,
    tipo VARCHAR(15) NOT NULL CHECK (tipo IN ('cliente', 'proveedor')),
    nombre VARCHAR(150) NOT NULL,
    identificador_fiscal VARCHAR(40) NOT NULL,
    email VARCHAR(150) NULL,
    telefono VARCHAR(30) NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_terceros_tipo_identificador UNIQUE (tipo, identificador_fiscal)
);

CREATE TABLE facturas (
    id SERIAL PRIMARY KEY,
    tipo VARCHAR(3) NOT NULL CHECK (tipo IN ('CXP', 'CXC')),
    tercero_id INTEGER NOT NULL REFERENCES terceros(id),
    numero_documento VARCHAR(60) NOT NULL,
    fecha_emision DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    subtotal NUMERIC(14, 2) NOT NULL CHECK (subtotal >= 0),
    iva NUMERIC(14, 2) NOT NULL CHECK (iva >= 0),
    total NUMERIC(14, 2) NOT NULL CHECK (total >= 0),
    estado VARCHAR(30) NOT NULL DEFAULT 'pendiente_aprobacion'
        CHECK (estado IN ('pendiente_aprobacion', 'aprobada', 'rechazada', 'parcial', 'pagada')),
    motivo_rechazo TEXT NULL,
    registrado_por INTEGER NOT NULL REFERENCES usuarios(id),
    aprobado_por INTEGER NULL REFERENCES usuarios(id),
    fecha_aprobacion TIMESTAMPTZ NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_facturas_total CHECK (total = subtotal + iva),
    CONSTRAINT chk_facturas_fechas CHECK (fecha_vencimiento >= fecha_emision),
    CONSTRAINT uq_facturas_duplicado UNIQUE (tipo, tercero_id, numero_documento, fecha_emision, total)
);

CREATE INDEX idx_facturas_tipo_estado ON facturas (tipo, estado) WHERE activo = TRUE;
CREATE INDEX idx_facturas_vencimiento ON facturas (fecha_vencimiento) WHERE activo = TRUE;
CREATE INDEX idx_facturas_tercero ON facturas (tercero_id) WHERE activo = TRUE;

CREATE TABLE documentos (
    id SERIAL PRIMARY KEY,
    factura_id INTEGER NOT NULL REFERENCES facturas(id),
    nombre_original VARCHAR(255) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL UNIQUE,
    mime VARCHAR(100) NOT NULL,
    tamano BIGINT NOT NULL CHECK (tamano > 0),
    subido_por INTEGER NOT NULL REFERENCES usuarios(id),
    creado_en TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_documentos_factura ON documentos (factura_id);

CREATE TABLE pagos (
    id SERIAL PRIMARY KEY,
    factura_id INTEGER NOT NULL REFERENCES facturas(id),
    fecha DATE NOT NULL,
    monto NUMERIC(14, 2) NOT NULL CHECK (monto > 0),
    metodo VARCHAR(40) NOT NULL,
    referencia VARCHAR(100) NULL,
    registrado_por INTEGER NOT NULL REFERENCES usuarios(id),
    creado_en TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_pagos_factura ON pagos (factura_id);

CREATE TABLE bitacora (
    id BIGSERIAL PRIMARY KEY,
    usuario_id INTEGER NULL REFERENCES usuarios(id),
    fecha TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    accion VARCHAR(60) NOT NULL,
    entidad VARCHAR(60) NOT NULL,
    entidad_id INTEGER NULL,
    valor_anterior JSONB NULL,
    valor_nuevo JSONB NULL,
    motivo TEXT NULL,
    ip VARCHAR(45) NULL
);

CREATE INDEX idx_bitacora_fecha ON bitacora (fecha DESC);
CREATE INDEX idx_bitacora_entidad ON bitacora (entidad, entidad_id);
CREATE INDEX idx_bitacora_usuario ON bitacora (usuario_id);

CREATE OR REPLACE FUNCTION fn_bitacora_inmutable()
RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'La bitacora es inmutable: no se permite %', TG_OP;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_bitacora_inmutable
BEFORE UPDATE OR DELETE ON bitacora
FOR EACH ROW EXECUTE FUNCTION fn_bitacora_inmutable();

CREATE TABLE parametros (
    clave VARCHAR(60) PRIMARY KEY,
    valor VARCHAR(255) NOT NULL
);

INSERT INTO parametros (clave, valor) VALUES
    ('iva_porcentaje', '13'),
    ('dias_alerta_vencimiento', '5')
ON CONFLICT (clave) DO NOTHING;
