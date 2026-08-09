-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 08-08-2026 a las 23:54:25
-- Versión del servidor: 11.8.8-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u348616500_sigesto`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`u348616500_sigesto`@`127.0.0.1` PROCEDURE `sp_recalcular_totales_cotizacion` (IN `p_id_cotizacion` BIGINT UNSIGNED)   BEGIN
                DECLARE v_subtotal DECIMAL(12,2);
                DECLARE v_tasa_igv DECIMAL(5,2);

                SELECT IFNULL(SUM(subtotal),0) INTO v_subtotal FROM detalle_cotizacion WHERE id_cotizacion = p_id_cotizacion;
                SELECT tasa_igv INTO v_tasa_igv FROM cotizaciones WHERE id_cotizacion = p_id_cotizacion;

                UPDATE cotizaciones
                SET subtotal = v_subtotal, igv = ROUND(v_subtotal * (v_tasa_igv / 100), 2), total = ROUND(v_subtotal * (1 + (v_tasa_igv / 100)), 2)
                WHERE id_cotizacion = p_id_cotizacion;
            END$$

CREATE DEFINER=`u348616500_sigesto`@`127.0.0.1` PROCEDURE `sp_verificar_conflicto_coordinacion` (IN `p_id_tecnico` BIGINT UNSIGNED, IN `p_fecha_coordinada` DATE, IN `p_hora_coordinada` TIME, IN `p_uuid_solicitud_excluir` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci)   BEGIN
                DECLARE v_conflicto_existe INT DEFAULT 0;
                DECLARE v_uuid_conflicto CHAR(36);
                DECLARE v_hora_conflicto TIME;
                DECLARE v_direccion_conflicto VARCHAR(255);

                SET @hora_inicio = DATE_SUB(p_hora_coordinada, INTERVAL 1 HOUR);
                SET @hora_fin = DATE_ADD(p_hora_coordinada, INTERVAL 1 HOUR);

                SELECT COUNT(*), MAX(uuid_solicitud), MAX(hora_coordinada), MAX(direccion_servicio)
                INTO v_conflicto_existe, v_uuid_conflicto, v_hora_conflicto, v_direccion_conflicto
                FROM solicitudes
                WHERE id_tecnico = p_id_tecnico
                    AND fecha_coordinada = p_fecha_coordinada
                    AND hora_coordinada IS NOT NULL
                    AND hora_coordinada BETWEEN @hora_inicio AND @hora_fin
                    AND (p_uuid_solicitud_excluir IS NULL OR uuid_solicitud COLLATE utf8mb4_unicode_ci != p_uuid_solicitud_excluir COLLATE utf8mb4_unicode_ci)
                    AND estado IN ('ASIGNADA', 'EN_PROCESO', 'COTIZADA', 'REVISION_PAGO', 'APROBADA')
                    AND deleted_at IS NULL;

                IF v_conflicto_existe > 0 THEN
                    SELECT 1 AS tiene_conflicto, v_uuid_conflicto AS uuid_solicitud_conflicto,
                        v_hora_conflicto AS hora_coordinada_conflicto, v_direccion_conflicto AS direccion_conflicto,
                        CONCAT('El técnico ya tiene una visita coordinada a las ', TIME_FORMAT(v_hora_conflicto, '%H:%i'), ' del mismo día en ', v_direccion_conflicto) AS mensaje_conflicto;
                ELSE
                    SELECT 0 AS tiene_conflicto, NULL AS uuid_solicitud_conflicto, NULL AS hora_coordinada_conflicto,
                        NULL AS direccion_conflicto, 'No hay conflictos de coordinación' AS mensaje_conflicto;
                END IF;
            END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones`
--

CREATE TABLE `cotizaciones` (
  `id_cotizacion` bigint(20) UNSIGNED NOT NULL,
  `uuid_solicitud` char(36) NOT NULL,
  `estado` enum('BORRADOR','ENVIADA','APROBADA','RECHAZADA','LIQUIDADA') NOT NULL DEFAULT 'BORRADOR',
  `tasa_igv` decimal(5,2) NOT NULL DEFAULT 18.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `igv` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `id_usuario_creador` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cotizaciones`
--

INSERT INTO `cotizaciones` (`id_cotizacion`, `uuid_solicitud`, `estado`, `tasa_igv`, `subtotal`, `igv`, `total`, `id_usuario_creador`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 'APROBADA', 18.00, 285.00, 51.30, 336.30, 3, '2026-07-16 17:50:53', '2026-07-16 18:03:47', NULL),
(2, '019f6c0e-2554-70ed-94ed-4d1ba8283952', 'LIQUIDADA', 18.00, 150.00, 27.00, 177.00, 3, '2026-07-16 17:51:34', '2026-07-16 18:03:43', NULL),
(3, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 'APROBADA', 18.00, 115.00, 20.70, 135.70, 3, '2026-07-16 23:02:38', '2026-07-17 03:17:31', NULL),
(4, '019f6e0b-56eb-737e-ae10-cf9781a6c4d3', 'LIQUIDADA', 18.00, 117.50, 21.15, 138.65, 3, '2026-07-17 03:07:44', '2026-07-22 17:43:10', NULL),
(5, '019f6e20-8fc3-73ea-98ce-8cf95b195e77', 'BORRADOR', 18.00, 0.00, 0.00, 0.00, 3, '2026-07-17 03:30:55', '2026-07-17 03:30:55', NULL),
(6, '019f6e42-efc0-71b9-9d1a-563e7389dc83', 'ENVIADA', 18.00, 43.50, 7.83, 51.33, 3, '2026-07-17 04:08:28', '2026-07-22 14:58:31', NULL),
(7, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'APROBADA', 18.00, 115.00, 20.70, 135.70, 3, '2026-07-17 13:12:13', '2026-07-17 14:07:50', NULL),
(8, '019f7083-54b5-70e1-a997-03c9ac026cfa', 'ENVIADA', 18.00, 102.50, 18.45, 120.95, 3, '2026-07-17 14:38:02', '2026-07-22 14:47:14', NULL),
(9, '019f7106-9335-72c0-905d-7f4a0ece9154', 'BORRADOR', 18.00, 0.00, 0.00, 0.00, 3, '2026-07-17 17:01:24', '2026-07-17 17:01:24', NULL),
(10, '019f711d-73b3-70f8-b479-d731e221ea77', 'BORRADOR', 18.00, 0.00, 0.00, 0.00, 3, '2026-07-17 17:26:23', '2026-07-17 17:26:23', NULL),
(11, '019f78af-d196-7192-b9d3-e1f3386c0913', 'APROBADA', 18.00, 115.00, 20.70, 135.70, 3, '2026-07-19 04:43:36', '2026-07-20 03:11:46', NULL),
(12, '019f7c61-5df2-7290-9a72-9b5e58440eb4', 'LIQUIDADA', 18.00, 192.00, 34.56, 226.56, 3, '2026-07-19 21:56:23', '2026-07-20 03:11:55', NULL),
(13, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'APROBADA', 18.00, 124.50, 22.41, 146.91, 3, '2026-07-21 14:16:35', '2026-07-21 18:00:58', NULL),
(14, '019f85c3-be5c-7360-b762-7c92cdd2fb38', 'LIQUIDADA', 18.00, 124.50, 22.41, 146.91, 3, '2026-07-21 17:40:25', '2026-07-22 17:18:51', NULL),
(15, '019f8a16-aaaa-7152-bd8b-30b3a85ce4bb', 'APROBADA', 18.00, 815.00, 146.70, 961.70, 3, '2026-07-22 13:49:29', '2026-07-22 14:09:57', NULL),
(16, '019f8a64-e3b6-70ba-a944-ad919090b49b', 'LIQUIDADA', 18.00, 802.50, 144.45, 946.95, 3, '2026-07-22 15:14:55', '2026-07-22 15:19:30', NULL),
(17, '019f8a72-1c8e-72e5-a4ff-742648fb5881', 'LIQUIDADA', 18.00, 39.50, 7.11, 46.61, 3, '2026-07-22 15:29:22', '2026-07-22 15:32:15', NULL),
(18, '019f8a75-c355-70e3-ae6a-51fd2cd29891', 'BORRADOR', 18.00, 0.00, 0.00, 0.00, 3, '2026-07-22 15:33:21', '2026-07-22 15:33:21', NULL),
(19, '019f8a77-6830-70c5-bfc9-b2c59abc1254', 'LIQUIDADA', 18.00, 254.50, 45.81, 300.31, 3, '2026-07-22 15:35:09', '2026-07-22 15:36:29', NULL),
(20, '019f8a81-d9ad-71da-b4d0-453bf061abcf', 'LIQUIDADA', 18.00, 504.50, 90.81, 595.31, 3, '2026-07-22 15:46:33', '2026-07-22 15:52:49', NULL),
(21, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 'LIQUIDADA', 18.00, 39.50, 7.11, 46.61, 3, '2026-07-22 15:58:19', '2026-07-22 16:03:35', NULL),
(22, '019f8a94-f17d-736f-8c8e-ea6e6833cd65', 'LIQUIDADA', 18.00, 124.50, 22.41, 146.91, 3, '2026-07-22 16:07:24', '2026-07-22 16:18:24', NULL),
(23, '019f8a9f-eb47-7132-93d3-44f31997cf4d', 'LIQUIDADA', 18.00, 39.50, 7.11, 46.61, 3, '2026-07-22 16:19:24', '2026-07-22 16:22:08', NULL),
(24, '019f8aa4-d04e-715a-b720-c33e14ad350d', 'LIQUIDADA', 18.00, 124.50, 22.41, 146.91, 3, '2026-07-22 16:24:44', '2026-07-22 16:39:24', NULL),
(25, '019f8ab0-dd5d-7272-8186-74d40aa90cd6', 'ENVIADA', 18.00, 804.50, 144.81, 949.31, 3, '2026-07-22 16:37:54', '2026-07-22 17:41:05', NULL),
(26, '019f8ab3-3cc3-707c-a442-52549bb86c48', 'LIQUIDADA', 18.00, 124.50, 22.41, 146.91, 3, '2026-07-22 16:40:30', '2026-07-22 17:02:13', NULL),
(27, '019f8ab3-9ed7-7133-8d0f-2c6803585017', 'LIQUIDADA', 18.00, 504.50, 90.81, 595.31, 3, '2026-07-22 16:40:55', '2026-07-22 16:53:02', NULL),
(28, '019f8ac8-5b1d-73b1-a5f6-1a806b520762', 'LIQUIDADA', 18.00, 504.50, 90.81, 595.31, 3, '2026-07-22 17:03:34', '2026-07-22 17:08:52', NULL),
(29, '019f8ad0-81f0-7128-989b-2b965a3d98ac', 'LIQUIDADA', 18.00, 124.50, 22.41, 146.91, 3, '2026-07-22 17:12:28', '2026-07-22 17:33:33', NULL),
(30, '019f8aee-8b36-714c-9868-af4614836584', 'LIQUIDADA', 18.00, 39.50, 7.11, 46.61, 3, '2026-07-22 17:45:16', '2026-07-22 17:47:38', NULL),
(31, '019f8af2-59d7-7004-9828-ec5bd5bfba32', 'LIQUIDADA', 18.00, 124.50, 22.41, 146.91, 3, '2026-07-22 17:49:26', '2026-07-22 17:52:10', NULL),
(32, '019f8afd-33c1-70ce-a9c9-ee24f601b048', 'ENVIADA', 18.00, 39.50, 7.11, 46.61, 3, '2026-07-22 18:01:17', '2026-07-22 18:10:38', NULL),
(33, '019f8afd-7f4a-7026-aa37-75c192d4c044', 'ENVIADA', 18.00, 504.50, 90.81, 595.31, 3, '2026-07-22 18:01:36', '2026-07-31 14:38:40', NULL),
(34, '019f8b01-7950-7146-91dc-433dc11250ae', 'LIQUIDADA', 18.00, 504.50, 90.81, 595.31, 3, '2026-07-22 18:05:57', '2026-07-22 18:13:59', NULL),
(35, '019f8b01-b8b5-7060-a859-70f8d2ac6042', 'LIQUIDADA', 18.00, 124.50, 22.41, 146.91, 3, '2026-07-22 18:06:13', '2026-07-22 18:13:19', NULL),
(36, '019f8f86-1ce0-7159-b91d-912c430ff512', 'LIQUIDADA', 18.00, 39.50, 7.11, 46.61, 3, '2026-07-23 15:09:18', '2026-07-23 15:34:20', NULL),
(37, '019f8f86-63de-714d-9b70-6683256467a9', 'LIQUIDADA', 18.00, 41.00, 7.38, 48.38, 3, '2026-07-23 15:09:37', '2026-07-23 15:30:58', NULL),
(38, '019f8fae-f51f-734c-85ce-9f231354d96e', 'LIQUIDADA', 18.00, 49.50, 8.91, 58.41, 3, '2026-07-23 15:53:55', '2026-07-23 16:09:02', NULL),
(39, '019f8faf-cefc-7317-94a7-7aa2708626ad', 'BORRADOR', 18.00, 0.00, 0.00, 0.00, 7, '2026-07-23 15:54:51', '2026-07-23 15:54:51', NULL),
(40, '019f8fb0-d671-7311-a896-8dffb0622375', 'BORRADOR', 18.00, 0.00, 0.00, 0.00, 6, '2026-07-23 15:55:58', '2026-07-23 15:55:58', NULL),
(41, '019fb888-962f-7202-84ae-28f2e94b74e2', 'ENVIADA', 18.00, 41.50, 7.47, 48.97, 3, '2026-07-31 14:16:26', '2026-07-31 14:39:18', NULL),
(42, '019fb8ad-c653-7347-85de-8bebae26f5b3', 'LIQUIDADA', 18.00, 39.50, 7.11, 46.61, 3, '2026-07-31 14:57:04', '2026-07-31 15:22:11', NULL),
(43, '019fb8ca-81c7-72a3-9556-e843ae93f217', 'LIQUIDADA', 18.00, 39.50, 7.11, 46.61, 3, '2026-07-31 15:28:27', '2026-07-31 15:33:45', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_cotizacion`
--

CREATE TABLE `detalle_cotizacion` (
  `id_detalle` bigint(20) UNSIGNED NOT NULL,
  `id_cotizacion` bigint(20) UNSIGNED NOT NULL,
  `id_item` bigint(20) UNSIGNED NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_aplicado` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) GENERATED ALWAYS AS (`cantidad` * `precio_aplicado`) STORED
) ;

--
-- Volcado de datos para la tabla `detalle_cotizacion`
--

INSERT INTO `detalle_cotizacion` (`id_detalle`, `id_cotizacion`, `id_item`, `cantidad`, `precio_aplicado`) VALUES
(1, 2, 3, 1.00, 35.00),
(2, 2, 17, 1.00, 80.00),
(3, 2, 19, 1.00, 35.00),
(4, 1, 3, 1.00, 35.00),
(5, 1, 18, 1.00, 250.00),
(6, 3, 3, 1.00, 35.00),
(7, 3, 17, 1.00, 80.00),
(8, 7, 3, 1.00, 35.00),
(9, 7, 17, 1.00, 80.00),
(10, 11, 3, 1.00, 35.00),
(11, 11, 17, 1.00, 80.00),
(12, 12, 3, 1.00, 35.00),
(13, 12, 4, 1.00, 42.00),
(14, 12, 17, 1.00, 80.00),
(15, 12, 19, 1.00, 35.00),
(16, 13, 13, 1.00, 4.50),
(17, 13, 16, 1.00, 120.00),
(18, 4, 3, 1.00, 35.00),
(19, 4, 1, 1.00, 2.50),
(20, 4, 17, 1.00, 80.00),
(21, 15, 3, 1.00, 35.00),
(22, 15, 15, 1.00, 500.00),
(23, 15, 5, 1.00, 280.00),
(24, 8, 1, 1.00, 2.50),
(25, 8, 20, 1.00, 100.00),
(26, 6, 19, 1.00, 35.00),
(27, 6, 7, 1.00, 8.50),
(28, 14, 13, 1.00, 4.50),
(29, 14, 16, 1.00, 120.00),
(30, 16, 1, 1.00, 2.50),
(31, 16, 20, 1.00, 800.00),
(32, 17, 13, 1.00, 4.50),
(33, 17, 19, 1.00, 35.00),
(34, 19, 13, 1.00, 4.50),
(35, 19, 18, 1.00, 250.00),
(36, 20, 13, 1.00, 4.50),
(37, 20, 15, 1.00, 500.00),
(38, 21, 13, 1.00, 4.50),
(39, 21, 19, 1.00, 35.00),
(40, 22, 13, 1.00, 4.50),
(41, 22, 16, 1.00, 120.00),
(42, 23, 13, 1.00, 4.50),
(43, 23, 19, 1.00, 35.00),
(44, 24, 13, 1.00, 4.50),
(45, 24, 16, 1.00, 120.00),
(46, 27, 13, 1.00, 4.50),
(47, 27, 15, 1.00, 500.00),
(48, 26, 13, 1.00, 4.50),
(49, 26, 16, 1.00, 120.00),
(50, 28, 13, 1.00, 4.50),
(51, 28, 15, 1.00, 500.00),
(52, 29, 13, 1.00, 4.50),
(53, 29, 16, 1.00, 120.00),
(54, 25, 13, 1.00, 4.50),
(55, 25, 20, 1.00, 800.00),
(56, 30, 13, 1.00, 4.50),
(57, 30, 19, 1.00, 35.00),
(58, 31, 13, 1.00, 4.50),
(59, 31, 16, 1.00, 120.00),
(60, 35, 13, 1.00, 4.50),
(61, 35, 16, 1.00, 120.00),
(62, 34, 13, 1.00, 4.50),
(63, 34, 15, 1.00, 500.00),
(64, 32, 13, 1.00, 4.50),
(65, 32, 19, 1.00, 35.00),
(66, 37, 19, 1.00, 35.00),
(67, 37, 8, 1.00, 6.00),
(68, 36, 13, 1.00, 4.50),
(69, 36, 19, 1.00, 35.00),
(70, 38, 19, 1.00, 35.00),
(71, 38, 8, 1.00, 6.00),
(72, 38, 7, 1.00, 8.50),
(73, 33, 13, 1.00, 4.50),
(74, 33, 15, 1.00, 500.00),
(75, 41, 11, 1.00, 6.50),
(76, 41, 19, 1.00, 35.00),
(77, 42, 13, 1.00, 4.50),
(78, 42, 19, 1.00, 35.00),
(79, 43, 13, 1.00, 4.50),
(80, 43, 19, 1.00, 35.00);

--
-- Disparadores `detalle_cotizacion`
--
DELIMITER $$
CREATE TRIGGER `trg_detalle_cotizacion_ad` AFTER DELETE ON `detalle_cotizacion` FOR EACH ROW BEGIN CALL sp_recalcular_totales_cotizacion(OLD.id_cotizacion); END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_detalle_cotizacion_ai` AFTER INSERT ON `detalle_cotizacion` FOR EACH ROW BEGIN CALL sp_recalcular_totales_cotizacion(NEW.id_cotizacion); END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_detalle_cotizacion_au` AFTER UPDATE ON `detalle_cotizacion` FOR EACH ROW BEGIN CALL sp_recalcular_totales_cotizacion(NEW.id_cotizacion); END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evidencias`
--

CREATE TABLE `evidencias` (
  `id_evidencia` bigint(20) UNSIGNED NOT NULL,
  `uuid_solicitud` char(36) NOT NULL,
  `tipo_evidencia` enum('FOTO_ANTES','FOTO_DESPUES','COMPROBANTE_PAGO') NOT NULL,
  `url_archivo` varchar(500) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `evidencias`
--

INSERT INTO `evidencias` (`id_evidencia`, `uuid_solicitud`, `tipo_evidencia`, `url_archivo`, `observaciones`, `fecha_subida`, `deleted_at`) VALUES
(1, '019f6c0e-2554-70ed-94ed-4d1ba8283952', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784225035/sigesto/evidencias/loldda24rp9zvgwhajt5.jpg', NULL, '2026-07-16 18:03:55', NULL),
(2, '019f6c0e-2554-70ed-94ed-4d1ba8283952', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784225046/sigesto/evidencias/dvbn2tw7sbonufeyuok4.jpg', NULL, '2026-07-16 18:04:07', NULL),
(3, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784225065/sigesto/evidencias/awmvikzydhbbjilcmbe2.jpg', NULL, '2026-07-16 18:04:25', NULL),
(4, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784225065/sigesto/evidencias/cesd85m3nlgqpr8c6nsw.jpg', NULL, '2026-07-16 18:04:26', NULL),
(5, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784225079/sigesto/evidencias/shfedfpcyc0w9hcvkycv.jpg', NULL, '2026-07-16 18:04:39', NULL),
(6, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784225079/sigesto/evidencias/qifgu2ruykhe4tfarp1l.jpg', NULL, '2026-07-16 18:04:40', NULL),
(7, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784225080/sigesto/evidencias/rj1hv2fxjb9rix7q7qqg.jpg', NULL, '2026-07-16 18:04:41', NULL),
(8, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784258491/sigesto/evidencias/idimjvzsqnus7xp2sp5p.jpg', NULL, '2026-07-17 03:21:31', NULL),
(9, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784258502/sigesto/evidencias/uigopneosvgrfq46rxwg.jpg', NULL, '2026-07-17 03:21:42', NULL),
(10, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784258508/sigesto/evidencias/ibdukr544n3ujyz6c1av.jpg', NULL, '2026-07-17 03:21:49', NULL),
(11, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784258519/sigesto/evidencias/bbkuedoohsfdjq3qbmj2.jpg', NULL, '2026-07-17 03:22:00', NULL),
(12, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784297404/sigesto/evidencias/emfelykwytggdeunntch.jpg', NULL, '2026-07-17 14:10:04', NULL),
(13, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784297418/sigesto/evidencias/zntsqzocubvo62pvq5i4.jpg', NULL, '2026-07-17 14:10:18', NULL),
(14, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784297672/sigesto/evidencias/yukhmnxqihiathpryhpw.jpg', NULL, '2026-07-17 14:14:33', NULL),
(15, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784298587/sigesto/evidencias/kdlhnbc8b6bw1jz5a6hf.jpg', NULL, '2026-07-17 14:29:47', NULL),
(16, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784298599/sigesto/evidencias/fkco0uo3ctgtkhh35oql.jpg', NULL, '2026-07-17 14:30:00', NULL),
(17, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784298614/sigesto/evidencias/okmehqxmr2jrxrnt2lof.jpg', NULL, '2026-07-17 14:30:14', NULL),
(18, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784298950/sigesto/evidencias/ix61j7r24wsesbzbpacg.jpg', NULL, '2026-07-17 14:35:51', NULL),
(19, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784298964/sigesto/evidencias/pfsssbfuglksxe2axouj.jpg', NULL, '2026-07-17 14:36:04', NULL),
(20, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784299003/sigesto/evidencias/blwzteblwg4sjrnljzhe.jpg', NULL, '2026-07-17 14:36:44', NULL),
(21, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784299021/sigesto/evidencias/n4d3l3vfklhgfcehybbq.jpg', NULL, '2026-07-17 14:37:01', NULL),
(22, '019f78af-d196-7192-b9d3-e1f3386c0913', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784656378/sigesto/evidencias/emwx5hlwd2ylqdqhqlf5.jpg', NULL, '2026-07-21 17:52:58', NULL),
(23, '019f78af-d196-7192-b9d3-e1f3386c0913', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784656392/sigesto/evidencias/rrzzn8ggxfwaqbt6ohsz.jpg', NULL, '2026-07-21 17:53:12', NULL),
(24, '019f78af-d196-7192-b9d3-e1f3386c0913', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784656399/sigesto/evidencias/txldialgsq3zxg3zyrdo.jpg', NULL, '2026-07-21 17:53:20', NULL),
(25, '019f78af-d196-7192-b9d3-e1f3386c0913', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784656407/sigesto/evidencias/hwt9tamizqilh3t7jfhs.jpg', NULL, '2026-07-21 17:53:27', NULL),
(26, '019f7c61-5df2-7290-9a72-9b5e58440eb4', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784656420/sigesto/evidencias/pohlsrjvc2wesnxpcidz.jpg', NULL, '2026-07-21 17:53:40', NULL),
(27, '019f7c61-5df2-7290-9a72-9b5e58440eb4', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784656432/sigesto/evidencias/pmwqwzxzphs5plvkbmvq.jpg', NULL, '2026-07-21 17:53:53', NULL),
(28, '019f78af-d196-7192-b9d3-e1f3386c0913', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784656450/sigesto/evidencias/lvh1j0m0ylfm6sqlwaql.jpg', NULL, '2026-07-21 17:54:10', NULL),
(29, '019f78af-d196-7192-b9d3-e1f3386c0913', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784656463/sigesto/evidencias/wfd7qlwokc92abvtoptw.jpg', NULL, '2026-07-21 17:54:23', NULL),
(30, '019f78af-d196-7192-b9d3-e1f3386c0913', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784727369/sigesto/evidencias/dy2c8dufgmavd03qi6d7.jpg', NULL, '2026-07-22 13:36:10', NULL),
(31, '019f78af-d196-7192-b9d3-e1f3386c0913', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784727370/sigesto/evidencias/evva9h0dqs4jgxnjxx2t.jpg', NULL, '2026-07-22 13:36:10', NULL),
(32, '019f78af-d196-7192-b9d3-e1f3386c0913', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784727385/sigesto/evidencias/dfmydorfv1koa9dag5ar.jpg', NULL, '2026-07-22 13:36:25', NULL),
(33, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784727401/sigesto/evidencias/csglmqpq1e4oikvxbx7x.jpg', NULL, '2026-07-22 13:36:41', NULL),
(34, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784727416/sigesto/evidencias/npaozxzfhmlamkq5ijsm.jpg', NULL, '2026-07-22 13:36:57', NULL),
(35, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784727423/sigesto/evidencias/kneb69muld7ynagtvmtl.jpg', NULL, '2026-07-22 13:37:04', NULL),
(36, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784727922/sigesto/evidencias/pgbyyhodfcs8m6f37vmi.jpg', NULL, '2026-07-22 13:45:23', NULL),
(37, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784727930/sigesto/evidencias/nxpbx0if2awpxzxe5mpx.jpg', NULL, '2026-07-22 13:45:30', NULL),
(38, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784727937/sigesto/evidencias/ejcd8yolhjfgqjbhrvtt.jpg', NULL, '2026-07-22 13:45:38', NULL),
(39, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784728449/sigesto/evidencias/hsgt1jbeq1oiujoi1dfx.jpg', NULL, '2026-07-22 13:54:10', NULL),
(40, '019f8a16-aaaa-7152-bd8b-30b3a85ce4bb', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784729512/sigesto/evidencias/htj0docaccomnr0prw7z.jpg', NULL, '2026-07-22 14:11:52', NULL),
(41, '019f8a16-aaaa-7152-bd8b-30b3a85ce4bb', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784729525/sigesto/evidencias/dw9mwlnmqby0pnphgxtw.jpg', NULL, '2026-07-22 14:12:05', NULL),
(42, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784733514/sigesto/evidencias/wfsezcsfolphtctebzbu.jpg', NULL, '2026-07-22 15:18:34', NULL),
(43, '019f8a64-e3b6-70ba-a944-ad919090b49b', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784733551/sigesto/evidencias/gxdebgsoxs0kd8ua6l61.jpg', NULL, '2026-07-22 15:19:11', NULL),
(44, '019f8a64-e3b6-70ba-a944-ad919090b49b', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784733558/sigesto/evidencias/of7opcfffxaedpk8tsv4.jpg', NULL, '2026-07-22 15:19:19', NULL),
(45, '019f8a64-e3b6-70ba-a944-ad919090b49b', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784733567/sigesto/evidencias/mjej2qczmxnlzryb8eja.jpg', NULL, '2026-07-22 15:19:27', NULL),
(46, '019f8a64-e3b6-70ba-a944-ad919090b49b', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784733602/sigesto/evidencias/nato8ujjaffgsezng3jk.jpg', NULL, '2026-07-22 15:20:03', NULL),
(47, '019f8a64-e3b6-70ba-a944-ad919090b49b', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784733610/sigesto/evidencias/c654dh0qexjdrisnzrpq.jpg', NULL, '2026-07-22 15:20:10', NULL),
(48, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784733640/sigesto/evidencias/duyqcvtf04xswq7x8ppd.jpg', NULL, '2026-07-22 15:20:40', NULL),
(49, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784733648/sigesto/evidencias/rowjl2xcyvlnbofm3j4d.jpg', NULL, '2026-07-22 15:20:48', NULL),
(50, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784733665/sigesto/evidencias/u1aqaxam2oxrfcu84ozw.jpg', NULL, '2026-07-22 15:21:05', NULL),
(51, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784733861/sigesto/evidencias/bf0nf01heg7cqzdnfz8v.jpg', NULL, '2026-07-22 15:24:21', NULL),
(52, '019f8a72-1c8e-72e5-a4ff-742648fb5881', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784734280/sigesto/evidencias/f7tagcpwrqg2rscmnp8n.jpg', NULL, '2026-07-22 15:31:21', NULL),
(53, '019f8a72-1c8e-72e5-a4ff-742648fb5881', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784734298/sigesto/evidencias/nor56ipowmkloat134yb.jpg', NULL, '2026-07-22 15:31:38', NULL),
(54, '019f8a72-1c8e-72e5-a4ff-742648fb5881', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784734344/sigesto/evidencias/kkzffuqhqy2u6nqr5ctf.jpg', NULL, '2026-07-22 15:32:25', NULL),
(55, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784734824/sigesto/evidencias/fjfyoawltl2lbtc0sjuz.jpg', NULL, '2026-07-22 15:40:24', NULL),
(56, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784734831/sigesto/evidencias/vhrzuaocjdve9iyffue8.jpg', NULL, '2026-07-22 15:40:32', NULL),
(57, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784734841/sigesto/evidencias/yw3al3hfwrh70ldbe2ab.jpg', NULL, '2026-07-22 15:40:41', NULL),
(58, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784734855/sigesto/evidencias/rcpsggjukmagv7qdb6nu.jpg', NULL, '2026-07-22 15:40:56', NULL),
(59, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784735144/sigesto/evidencias/z53nypf8mtwlaumtkm1k.jpg', NULL, '2026-07-22 15:45:45', NULL),
(60, '019f8a81-d9ad-71da-b4d0-453bf061abcf', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784735484/sigesto/evidencias/uumjclovi0r2niwuowj6.jpg', NULL, '2026-07-22 15:51:24', NULL),
(61, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784736135/sigesto/evidencias/ofezlpjcbdbkhvlrfulj.jpg', NULL, '2026-07-22 16:02:15', NULL),
(62, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784736173/sigesto/evidencias/xh4crcv5becs8db7vjq1.jpg', NULL, '2026-07-22 16:02:54', NULL),
(63, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784736181/sigesto/evidencias/cvtxhqghckxu7xlfsppr.jpg', NULL, '2026-07-22 16:03:02', NULL),
(64, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784736203/sigesto/evidencias/euj6o3gdrgbxcprkyeyr.jpg', NULL, '2026-07-22 16:03:23', NULL),
(65, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784736213/sigesto/evidencias/jlmdppfaxoaq2vnkimb0.jpg', NULL, '2026-07-22 16:03:33', NULL),
(66, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784736223/sigesto/evidencias/gobnf7dwpyao3tbdgh0j.jpg', NULL, '2026-07-22 16:03:43', NULL),
(67, '019f8a94-f17d-736f-8c8e-ea6e6833cd65', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784736994/sigesto/evidencias/yfaq1qacttiqewkcrsjp.jpg', NULL, '2026-07-22 16:16:34', NULL),
(68, '019f8a94-f17d-736f-8c8e-ea6e6833cd65', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784737023/sigesto/evidencias/ocfcyoplgvlnebekn8oa.jpg', NULL, '2026-07-22 16:17:03', NULL),
(69, '019f8a94-f17d-736f-8c8e-ea6e6833cd65', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784737033/sigesto/evidencias/myaevz0zmhhkdg7npqbm.jpg', NULL, '2026-07-22 16:17:14', NULL),
(70, '019f8a94-f17d-736f-8c8e-ea6e6833cd65', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784737115/sigesto/evidencias/rp8lnee2k3ygw3cgmj8r.jpg', NULL, '2026-07-22 16:18:35', NULL),
(71, '019f8a9f-eb47-7132-93d3-44f31997cf4d', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784737286/sigesto/evidencias/pnrmxpisyitz859rducg.jpg', NULL, '2026-07-22 16:21:27', NULL),
(72, '019f8aa4-d04e-715a-b720-c33e14ad350d', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784737820/sigesto/evidencias/ycjqylu2sj1xzupjhyo3.jpg', NULL, '2026-07-22 16:30:20', NULL),
(73, '019f8aa4-d04e-715a-b720-c33e14ad350d', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784738440/sigesto/evidencias/kndrydjr9z8t1cozq3rv.jpg', NULL, '2026-07-22 16:40:41', NULL),
(74, '019f8a9f-eb47-7132-93d3-44f31997cf4d', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784738452/sigesto/evidencias/ymv2btblhl8kfnhl8v74.jpg', NULL, '2026-07-22 16:40:52', NULL),
(75, '019f8a81-d9ad-71da-b4d0-453bf061abcf', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784738464/sigesto/evidencias/w7wsgpfjyzut4yvc1wdh.jpg', NULL, '2026-07-22 16:41:04', NULL),
(76, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784738478/sigesto/evidencias/pkxotg8je5kbfzvyvpid.jpg', NULL, '2026-07-22 16:41:18', NULL),
(77, '019f8a77-6830-70c5-bfc9-b2c59abc1254', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784738490/sigesto/evidencias/hx0d8fch49ycspvtqjjh.jpg', NULL, '2026-07-22 16:41:31', NULL),
(78, '019f8a64-e3b6-70ba-a944-ad919090b49b', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784738503/sigesto/evidencias/daljvn41oz1o37qba5hr.jpg', NULL, '2026-07-22 16:41:44', NULL),
(79, '019f8a16-aaaa-7152-bd8b-30b3a85ce4bb', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784738518/sigesto/evidencias/epgzjqss4q24pkmjvpux.jpg', NULL, '2026-07-22 16:41:59', NULL),
(80, '019f8ab3-9ed7-7133-8d0f-2c6803585017', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784739258/sigesto/evidencias/glzhh7va6nvcwzlkcwkp.jpg', NULL, '2026-07-22 16:54:18', NULL),
(81, '019f8ab3-3cc3-707c-a442-52549bb86c48', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784739277/sigesto/evidencias/xic6qz1ykibhkmxtphcr.jpg', NULL, '2026-07-22 16:54:37', NULL),
(82, '019f8ab3-3cc3-707c-a442-52549bb86c48', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784739990/sigesto/evidencias/aelbmeldp5gzpektvuhn.jpg', NULL, '2026-07-22 17:06:30', NULL),
(83, '019f8ac8-5b1d-73b1-a5f6-1a806b520762', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784740018/sigesto/evidencias/yukhva7fj8amrge9hwn1.jpg', NULL, '2026-07-22 17:06:58', NULL),
(84, '019f8ac8-5b1d-73b1-a5f6-1a806b520762', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784740173/sigesto/evidencias/uvl45styi5qxcpeuuvkg.jpg', NULL, '2026-07-22 17:09:33', NULL),
(85, '019f85c3-be5c-7360-b762-7c92cdd2fb38', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784740681/sigesto/evidencias/kidf4muccao9fyijyd9g.jpg', NULL, '2026-07-22 17:18:02', NULL),
(86, '019f85c3-be5c-7360-b762-7c92cdd2fb38', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784740783/sigesto/evidencias/jmpfab6an2vp6odzsh9m.jpg', NULL, '2026-07-22 17:19:44', NULL),
(87, '019f8ad0-81f0-7128-989b-2b965a3d98ac', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784741536/sigesto/evidencias/budvjtuzmeu6b1zj3l5u.jpg', NULL, '2026-07-22 17:32:16', NULL),
(88, '019f8ad0-81f0-7128-989b-2b965a3d98ac', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784741653/sigesto/evidencias/unneofe4yqxboexo29ge.jpg', NULL, '2026-07-22 17:34:14', NULL),
(89, '019f6e0b-56eb-737e-ae10-cf9781a6c4d3', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784742149/sigesto/evidencias/lfym2l44f8gpur0meryi.jpg', NULL, '2026-07-22 17:42:29', NULL),
(90, '019f6e0b-56eb-737e-ae10-cf9781a6c4d3', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784742211/sigesto/evidencias/ig6bokjgsbjzkrjbe8yw.jpg', NULL, '2026-07-22 17:43:32', NULL),
(91, '019f8af2-59d7-7004-9828-ec5bd5bfba32', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784743662/sigesto/evidencias/rwrt5lflx1y0mbjz6v6c.jpg', NULL, '2026-07-22 18:07:42', NULL),
(92, '019f8af2-59d7-7004-9828-ec5bd5bfba32', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784743695/sigesto/evidencias/ciitlfyn1pbdhxl6rzep.jpg', NULL, '2026-07-22 18:08:16', NULL),
(93, '019f8aee-8b36-714c-9868-af4614836584', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784743859/sigesto/evidencias/itt5udp6y81ncigycphg.jpg', NULL, '2026-07-22 18:11:00', NULL),
(94, '019f8b01-7950-7146-91dc-433dc11250ae', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784743937/sigesto/evidencias/c6qj3jopplgzi0zwuits.jpg', NULL, '2026-07-22 18:12:17', NULL),
(95, '019f8af2-59d7-7004-9828-ec5bd5bfba32', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784744005/sigesto/evidencias/itfyv4ntnvndejibcopo.jpg', NULL, '2026-07-22 18:13:26', NULL),
(96, '019f8aee-8b36-714c-9868-af4614836584', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784813360/sigesto/evidencias/wp11oykr6h3uuh6fhpwo.jpg', NULL, '2026-07-23 13:29:20', NULL),
(97, '019f8aee-8b36-714c-9868-af4614836584', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784813360/sigesto/evidencias/a5lghh7x3uv6hhzzuobt.jpg', NULL, '2026-07-23 13:29:21', NULL),
(98, '019f8aee-8b36-714c-9868-af4614836584', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784814852/sigesto/evidencias/p7mzuhicqh5tfnknwuge.jpg', NULL, '2026-07-23 13:54:13', NULL),
(99, '019f8aee-8b36-714c-9868-af4614836584', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784814859/sigesto/evidencias/e53gnbvabsp5dyxedlqp.jpg', NULL, '2026-07-23 13:54:20', NULL),
(100, '019f8b01-b8b5-7060-a859-70f8d2ac6042', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784815837/sigesto/evidencias/gw5or9slnempcy16tdae.jpg', NULL, '2026-07-23 14:10:38', NULL),
(101, '019f8b01-b8b5-7060-a859-70f8d2ac6042', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784815846/sigesto/evidencias/en65pqczmyf3r6nzvivn.jpg', NULL, '2026-07-23 14:10:47', NULL),
(102, '019f8aee-8b36-714c-9868-af4614836584', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784818381/sigesto/evidencias/tfgssrl2oxlmfeiyrj7o.jpg', NULL, '2026-07-23 14:53:02', NULL),
(103, '019f8b01-b8b5-7060-a859-70f8d2ac6042', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784818399/sigesto/evidencias/fbrwcp6c25v7aq5n1cay.jpg', NULL, '2026-07-23 14:53:20', NULL),
(104, '019f8b01-b8b5-7060-a859-70f8d2ac6042', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784818412/sigesto/evidencias/xp86mnaxpr1rfizynf3o.jpg', NULL, '2026-07-23 14:53:33', NULL),
(105, '019f8b01-b8b5-7060-a859-70f8d2ac6042', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784818422/sigesto/evidencias/qb0dbcp7ley6ennz5jjl.jpg', NULL, '2026-07-23 14:53:42', NULL),
(106, '019f8b01-b8b5-7060-a859-70f8d2ac6042', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784818422/sigesto/evidencias/nhdqmhv4rychvbwam5he.jpg', NULL, '2026-07-23 14:53:43', NULL),
(107, '019f8b01-7950-7146-91dc-433dc11250ae', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784818433/sigesto/evidencias/mmzyekpychicjpkkjhyt.jpg', NULL, '2026-07-23 14:53:54', NULL),
(108, '019f8b01-b8b5-7060-a859-70f8d2ac6042', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784818659/sigesto/evidencias/c2kv3tvcdnl2967uikck.jpg', NULL, '2026-07-23 14:57:39', NULL),
(109, '019f8b01-b8b5-7060-a859-70f8d2ac6042', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784818681/sigesto/evidencias/hk8sjddqwy7jiragksun.jpg', NULL, '2026-07-23 14:58:02', NULL),
(110, '019f8b01-7950-7146-91dc-433dc11250ae', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784818710/sigesto/evidencias/pwxmqyuvuulh1nwtkmss.jpg', NULL, '2026-07-23 14:58:30', NULL),
(111, '019f8b01-7950-7146-91dc-433dc11250ae', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784818721/sigesto/evidencias/ybkdtw4kl4rmt2redw9w.jpg', NULL, '2026-07-23 14:58:41', NULL),
(112, '019f8af2-59d7-7004-9828-ec5bd5bfba32', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784818841/sigesto/evidencias/lmexeg3dp8h84s7rlgim.jpg', NULL, '2026-07-23 15:00:42', NULL),
(113, '019f8af2-59d7-7004-9828-ec5bd5bfba32', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784818849/sigesto/evidencias/be8flqkllpahq6cjkga7.jpg', NULL, '2026-07-23 15:00:50', NULL),
(114, '019f8aee-8b36-714c-9868-af4614836584', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784819348/sigesto/evidencias/wnvnunnuegfkhqscufom.jpg', NULL, '2026-07-23 15:09:09', NULL),
(115, '019f8aee-8b36-714c-9868-af4614836584', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784819356/sigesto/evidencias/zihrwrgk5afevhg1zdtu.jpg', NULL, '2026-07-23 15:09:17', NULL),
(116, '019f8f86-63de-714d-9b70-6683256467a9', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784820687/sigesto/evidencias/dm1z47jieiyll5lqgrrf.jpg', NULL, '2026-07-23 15:31:28', NULL),
(117, '019f8f86-63de-714d-9b70-6683256467a9', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784820717/sigesto/evidencias/nunmn4hwybcp7izmjldo.jpg', NULL, '2026-07-23 15:31:58', NULL),
(118, '019f8f86-1ce0-7159-b91d-912c430ff512', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784820834/sigesto/evidencias/sncnbkrpfsfpxlsf8eit.jpg', NULL, '2026-07-23 15:33:54', NULL),
(119, '019f8f86-1ce0-7159-b91d-912c430ff512', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784820884/sigesto/evidencias/a3r5bruhxvjtptlcfj4l.jpg', NULL, '2026-07-23 15:34:44', NULL),
(120, '019f8fae-f51f-734c-85ce-9f231354d96e', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784822815/sigesto/evidencias/ursd26quuxdjlzy5e1eq.jpg', NULL, '2026-07-23 16:06:56', NULL),
(121, '019f8fae-f51f-734c-85ce-9f231354d96e', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784822816/sigesto/evidencias/fd70zonpfl7dtnhb7qoc.jpg', NULL, '2026-07-23 16:06:56', NULL),
(122, '019f8fae-f51f-734c-85ce-9f231354d96e', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784822867/sigesto/evidencias/vasfxzv6vzs4v3cjx1ql.jpg', NULL, '2026-07-23 16:07:47', NULL),
(123, '019f8fae-f51f-734c-85ce-9f231354d96e', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784823430/sigesto/evidencias/ybs6mbk9lqoduc3n5dgi.jpg', NULL, '2026-07-23 16:17:11', NULL),
(124, '019fb8ad-c653-7347-85de-8bebae26f5b3', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1785510874/sigesto/evidencias/a3vxkcct5nvmlk3wnigi.jpg', NULL, '2026-07-31 15:14:34', NULL),
(125, '019fb8ad-c653-7347-85de-8bebae26f5b3', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1785511374/sigesto/evidencias/kiks3hs7801bcsnlqct1.jpg', NULL, '2026-07-31 15:22:54', NULL),
(126, '019fb8ad-c653-7347-85de-8bebae26f5b3', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1785511384/sigesto/evidencias/ndt50gm0pnwdv1ha59nt.jpg', NULL, '2026-07-31 15:23:04', NULL),
(127, '019fb8ca-81c7-72a3-9556-e843ae93f217', 'FOTO_ANTES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1785512090/sigesto/evidencias/irhakyurjlhibxcythel.jpg', NULL, '2026-07-31 15:34:50', NULL),
(128, '019fb8ca-81c7-72a3-9556-e843ae93f217', 'FOTO_DESPUES', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1785512109/sigesto/evidencias/vax81qg0wzrnvft8agrb.jpg', NULL, '2026-07-31 15:35:09', '2026-07-31 15:35:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_estados`
--

CREATE TABLE `historial_estados` (
  `id_historial` bigint(20) UNSIGNED NOT NULL,
  `uuid_solicitud` char(36) NOT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `estado_nuevo` varchar(50) NOT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_usuario_accion` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `historial_estados`
--

INSERT INTO `historial_estados` (`id_historial`, `uuid_solicitud`, `estado_anterior`, `estado_nuevo`, `fecha_cambio`, `id_usuario_accion`) VALUES
(1, '019f6c0d-8618-728c-9bfe-d537c0a80b81', NULL, 'PENDIENTE', '2026-07-16 17:50:53', 3),
(2, '019f6c0e-2554-70ed-94ed-4d1ba8283952', NULL, 'PENDIENTE', '2026-07-16 17:51:34', 3),
(3, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 'PENDIENTE', 'ASIGNADA', '2026-07-16 17:53:11', 1),
(4, '019f6c0e-2554-70ed-94ed-4d1ba8283952', 'PENDIENTE', 'ASIGNADA', '2026-07-16 17:53:34', 1),
(5, '019f6c0e-2554-70ed-94ed-4d1ba8283952', 'ASIGNADA', 'COTIZADA', '2026-07-16 17:55:22', 2),
(6, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 'ASIGNADA', 'COTIZADA', '2026-07-16 17:55:32', 2),
(7, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 'COTIZADA', 'REVISION_PAGO', '2026-07-16 17:56:34', 3),
(8, '019f6c0e-2554-70ed-94ed-4d1ba8283952', 'COTIZADA', 'REVISION_PAGO', '2026-07-16 17:57:06', 3),
(9, '019f6c0e-2554-70ed-94ed-4d1ba8283952', 'REVISION_PAGO', 'APROBADA', '2026-07-16 18:03:43', 1),
(10, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 'REVISION_PAGO', 'APROBADA', '2026-07-16 18:03:47', 1),
(11, '019f6c0e-2554-70ed-94ed-4d1ba8283952', 'APROBADA', 'EN_PROCESO', '2026-07-16 18:03:55', 2),
(12, '019f6c0e-2554-70ed-94ed-4d1ba8283952', 'EN_PROCESO', 'FINALIZADA', '2026-07-16 18:04:07', 2),
(13, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 'APROBADA', 'EN_PROCESO', '2026-07-16 18:04:27', 2),
(14, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 'EN_PROCESO', 'FINALIZADA', '2026-07-16 18:04:42', 2),
(15, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 'FINALIZADA', 'PAGADA', '2026-07-16 18:04:51', 2),
(16, '019f6d2a-f251-708d-92bf-c27d9f51cf00', NULL, 'PENDIENTE', '2026-07-16 23:02:38', 3),
(17, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 'PENDIENTE', 'ASIGNADA', '2026-07-17 02:59:08', 1),
(18, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 'ASIGNADA', 'COTIZADA', '2026-07-17 02:59:44', 2),
(19, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 'COTIZADA', 'REVISION_PAGO', '2026-07-17 03:07:18', 3),
(20, '019f6e0b-56eb-737e-ae10-cf9781a6c4d3', NULL, 'PENDIENTE', '2026-07-17 03:07:44', 3),
(21, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 'REVISION_PAGO', 'APROBADA', '2026-07-17 03:17:31', 1),
(22, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 'APROBADA', 'EN_PROCESO', '2026-07-17 03:21:32', 2),
(23, '019f6e20-8fc3-73ea-98ce-8cf95b195e77', NULL, 'PENDIENTE', '2026-07-17 03:30:55', 3),
(24, '019f6e42-efc0-71b9-9d1a-563e7389dc83', NULL, 'PENDIENTE', '2026-07-17 04:08:28', 3),
(25, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-17 11:10:05', 3),
(26, '019f7034-c3a5-73ef-988f-69086a7b34f7', NULL, 'PENDIENTE', '2026-07-17 13:12:13', 3),
(27, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'PENDIENTE', 'ASIGNADA', '2026-07-17 14:04:20', 1),
(28, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'ASIGNADA', 'COTIZADA', '2026-07-17 14:05:50', 2),
(29, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'COTIZADA', 'REVISION_PAGO', '2026-07-17 14:06:55', 3),
(30, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'REVISION_PAGO', 'APROBADA', '2026-07-17 14:07:50', 1),
(31, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'APROBADA', 'EN_PROCESO', '2026-07-17 14:10:05', 2),
(32, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 'REVISION_PAGO', 'APROBADA', '2026-07-17 14:28:13', 1),
(33, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-17 14:28:58', 3),
(34, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'REVISION_PAGO', 'APROBADA', '2026-07-17 14:29:31', 1),
(35, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 'APROBADA', 'EN_PROCESO', '2026-07-17 14:35:51', 2),
(36, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 'EN_PROCESO', 'FINALIZADA', '2026-07-17 14:36:05', 2),
(37, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'APROBADA', 'EN_PROCESO', '2026-07-17 14:36:44', 2),
(38, '019f7034-c3a5-73ef-988f-69086a7b34f7', 'EN_PROCESO', 'FINALIZADA', '2026-07-17 14:37:02', 2),
(39, '019f7083-54b5-70e1-a997-03c9ac026cfa', NULL, 'PENDIENTE', '2026-07-17 14:38:02', 3),
(40, '019f7106-9335-72c0-905d-7f4a0ece9154', NULL, 'PENDIENTE', '2026-07-17 17:01:24', 3),
(41, '019f711d-73b3-70f8-b479-d731e221ea77', NULL, 'PENDIENTE', '2026-07-17 17:26:23', 3),
(42, '019f78af-d196-7192-b9d3-e1f3386c0913', NULL, 'PENDIENTE', '2026-07-19 04:43:36', 3),
(43, '019f78af-d196-7192-b9d3-e1f3386c0913', 'EN_PROCESO', 'FINALIZADA', '2026-07-19 04:53:10', 2),
(44, '019f7c61-5df2-7290-9a72-9b5e58440eb4', NULL, 'PENDIENTE', '2026-07-19 21:56:23', 3),
(45, '019f78af-d196-7192-b9d3-e1f3386c0913', 'FINALIZADA', 'ASIGNADA', '2026-07-20 02:17:27', 1),
(46, '019f78af-d196-7192-b9d3-e1f3386c0913', 'ASIGNADA', 'COTIZADA', '2026-07-20 02:50:28', 2),
(47, '019f7c61-5df2-7290-9a72-9b5e58440eb4', 'PENDIENTE', 'ASIGNADA', '2026-07-20 02:56:09', 1),
(48, '019f7c61-5df2-7290-9a72-9b5e58440eb4', 'ASIGNADA', 'COTIZADA', '2026-07-20 02:57:06', 2),
(49, '019f7c61-5df2-7290-9a72-9b5e58440eb4', 'COTIZADA', 'REVISION_PAGO', '2026-07-20 03:02:05', 3),
(50, '019f78af-d196-7192-b9d3-e1f3386c0913', 'COTIZADA', 'REVISION_PAGO', '2026-07-20 03:03:29', 3),
(51, '019f78af-d196-7192-b9d3-e1f3386c0913', 'REVISION_PAGO', 'APROBADA', '2026-07-20 03:11:46', 1),
(52, '019f7c61-5df2-7290-9a72-9b5e58440eb4', 'REVISION_PAGO', 'APROBADA', '2026-07-20 03:11:55', 1),
(53, '019f6e0b-56eb-737e-ae10-cf9781a6c4d3', 'PENDIENTE', 'ASIGNADA', '2026-07-20 04:09:47', 1),
(54, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', NULL, 'PENDIENTE', '2026-07-21 14:16:35', 3),
(55, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'PENDIENTE', 'ASIGNADA', '2026-07-21 14:29:25', 1),
(56, '019f6e20-8fc3-73ea-98ce-8cf95b195e77', 'PENDIENTE', 'ASIGNADA', '2026-07-21 14:32:54', 1),
(57, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'ASIGNADA', 'COTIZADA', '2026-07-21 16:36:25', 2),
(58, '019f6e0b-56eb-737e-ae10-cf9781a6c4d3', 'ASIGNADA', 'COTIZADA', '2026-07-21 16:36:47', 2),
(59, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'COTIZADA', 'REVISION_PAGO', '2026-07-21 16:38:02', 3),
(60, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'REVISION_PAGO', 'REVISION_PAGO', '2026-07-21 16:56:52', 3),
(61, '019f85c3-be5c-7360-b762-7c92cdd2fb38', NULL, 'PENDIENTE', '2026-07-21 17:40:25', 3),
(62, '019f78af-d196-7192-b9d3-e1f3386c0913', 'APROBADA', 'EN_PROCESO', '2026-07-21 17:52:59', 2),
(63, '019f7c61-5df2-7290-9a72-9b5e58440eb4', 'APROBADA', 'EN_PROCESO', '2026-07-21 17:53:41', 2),
(64, '019f7c61-5df2-7290-9a72-9b5e58440eb4', 'EN_PROCESO', 'FINALIZADA', '2026-07-21 17:53:54', 2),
(65, '019f78af-d196-7192-b9d3-e1f3386c0913', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-21 17:57:18', 3),
(66, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'REVISION_PAGO', 'APROBADA', '2026-07-21 18:00:58', 1),
(67, '019f78af-d196-7192-b9d3-e1f3386c0913', 'REVISION_PAGO', 'APROBADA', '2026-07-21 18:01:07', 1),
(68, '019f78af-d196-7192-b9d3-e1f3386c0913', 'APROBADA', 'EN_PROCESO', '2026-07-22 13:36:11', 2),
(69, '019f78af-d196-7192-b9d3-e1f3386c0913', 'EN_PROCESO', 'FINALIZADA', '2026-07-22 13:36:26', 2),
(70, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 'APROBADA', 'EN_PROCESO', '2026-07-22 13:36:43', 2),
(71, '019f8a16-aaaa-7152-bd8b-30b3a85ce4bb', NULL, 'PENDIENTE', '2026-07-22 13:49:29', 3),
(72, '019f8a16-aaaa-7152-bd8b-30b3a85ce4bb', 'PENDIENTE', 'ASIGNADA', '2026-07-22 14:05:44', 1),
(73, '019f8a16-aaaa-7152-bd8b-30b3a85ce4bb', 'ASIGNADA', 'COTIZADA', '2026-07-22 14:07:15', 2),
(74, '019f8a16-aaaa-7152-bd8b-30b3a85ce4bb', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 14:08:03', 3),
(75, '019f8a16-aaaa-7152-bd8b-30b3a85ce4bb', 'REVISION_PAGO', 'APROBADA', '2026-07-22 14:09:57', 1),
(76, '019f8a16-aaaa-7152-bd8b-30b3a85ce4bb', 'APROBADA', 'EN_PROCESO', '2026-07-22 14:11:53', 2),
(77, '019f8a16-aaaa-7152-bd8b-30b3a85ce4bb', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-22 14:12:37', 3),
(78, '019f8a16-aaaa-7152-bd8b-30b3a85ce4bb', 'REVISION_PAGO', 'APROBADA', '2026-07-22 14:13:22', 1),
(79, '019f7083-54b5-70e1-a997-03c9ac026cfa', 'PENDIENTE', 'ASIGNADA', '2026-07-22 14:27:03', 1),
(80, '019f7083-54b5-70e1-a997-03c9ac026cfa', 'ASIGNADA', 'COTIZADA', '2026-07-22 14:47:14', 2),
(81, '019f7106-9335-72c0-905d-7f4a0ece9154', 'PENDIENTE', 'ASIGNADA', '2026-07-22 14:54:59', 1),
(82, '019f6e42-efc0-71b9-9d1a-563e7389dc83', 'PENDIENTE', 'ASIGNADA', '2026-07-22 14:57:32', 1),
(83, '019f6e42-efc0-71b9-9d1a-563e7389dc83', 'ASIGNADA', 'COTIZADA', '2026-07-22 14:58:31', 2),
(84, '019f85c3-be5c-7360-b762-7c92cdd2fb38', 'PENDIENTE', 'ASIGNADA', '2026-07-22 15:11:05', 1),
(85, '019f85c3-be5c-7360-b762-7c92cdd2fb38', 'ASIGNADA', 'COTIZADA', '2026-07-22 15:14:37', 2),
(86, '019f8a64-e3b6-70ba-a944-ad919090b49b', NULL, 'PENDIENTE', '2026-07-22 15:14:55', 3),
(87, '019f8a64-e3b6-70ba-a944-ad919090b49b', 'PENDIENTE', 'ASIGNADA', '2026-07-22 15:15:58', 1),
(88, '019f8a64-e3b6-70ba-a944-ad919090b49b', 'ASIGNADA', 'COTIZADA', '2026-07-22 15:17:11', 2),
(89, '019f8a64-e3b6-70ba-a944-ad919090b49b', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 15:17:33', 3),
(90, '019f8a64-e3b6-70ba-a944-ad919090b49b', 'REVISION_PAGO', 'EN_PROCESO', '2026-07-22 15:18:05', 1),
(91, '019f8a64-e3b6-70ba-a944-ad919090b49b', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-22 15:19:08', 3),
(92, '019f8a64-e3b6-70ba-a944-ad919090b49b', 'REVISION_PAGO', 'PAGADA', '2026-07-22 15:19:30', 1),
(93, '019f8a72-1c8e-72e5-a4ff-742648fb5881', NULL, 'PENDIENTE', '2026-07-22 15:29:22', 3),
(94, '019f8a72-1c8e-72e5-a4ff-742648fb5881', 'PENDIENTE', 'ASIGNADA', '2026-07-22 15:29:59', 1),
(95, '019f8a72-1c8e-72e5-a4ff-742648fb5881', 'ASIGNADA', 'COTIZADA', '2026-07-22 15:30:15', 2),
(96, '019f8a72-1c8e-72e5-a4ff-742648fb5881', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 15:30:33', 3),
(97, '019f8a72-1c8e-72e5-a4ff-742648fb5881', 'REVISION_PAGO', 'EN_PROCESO', '2026-07-22 15:30:56', 1),
(98, '019f8a72-1c8e-72e5-a4ff-742648fb5881', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-22 15:31:54', 3),
(99, '019f8a72-1c8e-72e5-a4ff-742648fb5881', 'REVISION_PAGO', 'PAGADA', '2026-07-22 15:32:15', 1),
(100, '019f8a72-1c8e-72e5-a4ff-742648fb5881', 'PAGADA', 'FINALIZADA', '2026-07-22 15:32:25', 2),
(101, '019f8a75-c355-70e3-ae6a-51fd2cd29891', NULL, 'PENDIENTE', '2026-07-22 15:33:21', 3),
(102, '019f8a75-c355-70e3-ae6a-51fd2cd29891', 'PENDIENTE', 'ASIGNADA', '2026-07-22 15:33:52', 1),
(103, '019f8a77-6830-70c5-bfc9-b2c59abc1254', NULL, 'PENDIENTE', '2026-07-22 15:35:09', 3),
(104, '019f8a77-6830-70c5-bfc9-b2c59abc1254', 'PENDIENTE', 'ASIGNADA', '2026-07-22 15:35:27', 1),
(105, '019f8a77-6830-70c5-bfc9-b2c59abc1254', 'ASIGNADA', 'COTIZADA', '2026-07-22 15:35:44', 2),
(106, '019f8a77-6830-70c5-bfc9-b2c59abc1254', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 15:36:01', 3),
(107, '019f8a77-6830-70c5-bfc9-b2c59abc1254', 'REVISION_PAGO', 'PAGADA', '2026-07-22 15:36:29', 1),
(108, '019f8a81-d9ad-71da-b4d0-453bf061abcf', NULL, 'PENDIENTE', '2026-07-22 15:46:33', 3),
(109, '019f8a81-d9ad-71da-b4d0-453bf061abcf', 'PENDIENTE', 'ASIGNADA', '2026-07-22 15:48:26', 1),
(110, '019f8a81-d9ad-71da-b4d0-453bf061abcf', 'ASIGNADA', 'COTIZADA', '2026-07-22 15:48:50', 2),
(111, '019f711d-73b3-70f8-b479-d731e221ea77', 'PENDIENTE', 'ASIGNADA', '2026-07-22 15:48:51', 1),
(112, '019f8a81-d9ad-71da-b4d0-453bf061abcf', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 15:49:19', 3),
(113, '019f8a81-d9ad-71da-b4d0-453bf061abcf', 'REVISION_PAGO', 'EN_PROCESO', '2026-07-22 15:49:36', 1),
(114, '019f8a81-d9ad-71da-b4d0-453bf061abcf', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-22 15:52:00', 3),
(115, '019f8a81-d9ad-71da-b4d0-453bf061abcf', 'REVISION_PAGO', 'PAGADA', '2026-07-22 15:52:49', 1),
(116, '019f8a8c-a04d-7355-9393-a5c2ccc05406', NULL, 'PENDIENTE', '2026-07-22 15:58:19', 3),
(117, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 'PENDIENTE', 'ASIGNADA', '2026-07-22 15:59:02', 1),
(118, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 'ASIGNADA', 'COTIZADA', '2026-07-22 16:00:20', 2),
(119, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 16:01:16', 3),
(120, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 'REVISION_PAGO', 'APROBADA', '2026-07-22 16:01:50', 1),
(121, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 'APROBADA', 'EN_PROCESO', '2026-07-22 16:02:16', 2),
(122, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-22 16:03:08', 3),
(123, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 'REVISION_PAGO', 'PAGADA', '2026-07-22 16:03:35', 1),
(124, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 'PAGADA', 'FINALIZADA', '2026-07-22 16:03:44', 2),
(125, '019f8a94-f17d-736f-8c8e-ea6e6833cd65', NULL, 'PENDIENTE', '2026-07-22 16:07:24', 3),
(126, '019f8a94-f17d-736f-8c8e-ea6e6833cd65', 'PENDIENTE', 'ASIGNADA', '2026-07-22 16:09:56', 1),
(127, '019f8a94-f17d-736f-8c8e-ea6e6833cd65', 'ASIGNADA', 'COTIZADA', '2026-07-22 16:14:09', 2),
(128, '019f8a94-f17d-736f-8c8e-ea6e6833cd65', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 16:14:40', 3),
(129, '019f8a94-f17d-736f-8c8e-ea6e6833cd65', 'REVISION_PAGO', 'APROBADA', '2026-07-22 16:16:02', 1),
(130, '019f8a94-f17d-736f-8c8e-ea6e6833cd65', 'APROBADA', 'EN_PROCESO', '2026-07-22 16:16:35', 2),
(131, '019f8a94-f17d-736f-8c8e-ea6e6833cd65', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-22 16:17:48', 3),
(132, '019f8a94-f17d-736f-8c8e-ea6e6833cd65', 'REVISION_PAGO', 'PAGADA', '2026-07-22 16:18:24', 1),
(133, '019f8a94-f17d-736f-8c8e-ea6e6833cd65', 'PAGADA', 'FINALIZADA', '2026-07-22 16:18:36', 2),
(134, '019f8a9f-eb47-7132-93d3-44f31997cf4d', NULL, 'PENDIENTE', '2026-07-22 16:19:24', 3),
(135, '019f8a9f-eb47-7132-93d3-44f31997cf4d', 'PENDIENTE', 'ASIGNADA', '2026-07-22 16:19:51', 1),
(136, '019f8a9f-eb47-7132-93d3-44f31997cf4d', 'ASIGNADA', 'COTIZADA', '2026-07-22 16:20:21', 2),
(137, '019f8a9f-eb47-7132-93d3-44f31997cf4d', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 16:20:44', 3),
(138, '019f8a9f-eb47-7132-93d3-44f31997cf4d', 'REVISION_PAGO', 'APROBADA', '2026-07-22 16:21:07', 1),
(139, '019f8a9f-eb47-7132-93d3-44f31997cf4d', 'APROBADA', 'EN_PROCESO', '2026-07-22 16:21:28', 2),
(140, '019f8a9f-eb47-7132-93d3-44f31997cf4d', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-22 16:21:47', 3),
(141, '019f8a9f-eb47-7132-93d3-44f31997cf4d', 'REVISION_PAGO', 'PAGADA', '2026-07-22 16:22:08', 1),
(142, '019f8aa4-d04e-715a-b720-c33e14ad350d', NULL, 'PENDIENTE', '2026-07-22 16:24:44', 3),
(143, '019f8aa4-d04e-715a-b720-c33e14ad350d', 'PENDIENTE', 'ASIGNADA', '2026-07-22 16:25:15', 1),
(144, '019f8aa4-d04e-715a-b720-c33e14ad350d', 'ASIGNADA', 'COTIZADA', '2026-07-22 16:28:21', 2),
(145, '019f8aa4-d04e-715a-b720-c33e14ad350d', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 16:28:47', 3),
(146, '019f8aa4-d04e-715a-b720-c33e14ad350d', 'REVISION_PAGO', 'APROBADA', '2026-07-22 16:29:11', 1),
(147, '019f8aa4-d04e-715a-b720-c33e14ad350d', 'APROBADA', 'EN_PROCESO', '2026-07-22 16:30:21', 2),
(148, '019f8aa4-d04e-715a-b720-c33e14ad350d', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-22 16:30:49', 3),
(149, '019f8ab0-dd5d-7272-8186-74d40aa90cd6', NULL, 'PENDIENTE', '2026-07-22 16:37:54', 3),
(150, '019f8aa4-d04e-715a-b720-c33e14ad350d', 'REVISION_PAGO', 'PAGADA', '2026-07-22 16:39:24', 1),
(151, '019f8ab3-3cc3-707c-a442-52549bb86c48', NULL, 'PENDIENTE', '2026-07-22 16:40:30', 3),
(152, '019f8aa4-d04e-715a-b720-c33e14ad350d', 'PAGADA', 'FINALIZADA', '2026-07-22 16:40:41', 2),
(153, '019f8a9f-eb47-7132-93d3-44f31997cf4d', 'PAGADA', 'FINALIZADA', '2026-07-22 16:40:53', 2),
(154, '019f8ab3-9ed7-7133-8d0f-2c6803585017', NULL, 'PENDIENTE', '2026-07-22 16:40:55', 3),
(155, '019f8a81-d9ad-71da-b4d0-453bf061abcf', 'PAGADA', 'FINALIZADA', '2026-07-22 16:41:05', 2),
(156, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 'PAGADA', 'FINALIZADA', '2026-07-22 16:41:19', 2),
(157, '019f8ab3-3cc3-707c-a442-52549bb86c48', 'PENDIENTE', 'ASIGNADA', '2026-07-22 16:41:22', 1),
(158, '019f8a77-6830-70c5-bfc9-b2c59abc1254', 'PAGADA', 'FINALIZADA', '2026-07-22 16:41:32', 2),
(159, '019f8a64-e3b6-70ba-a944-ad919090b49b', 'PAGADA', 'FINALIZADA', '2026-07-22 16:41:45', 2),
(160, '019f8ab3-9ed7-7133-8d0f-2c6803585017', 'PENDIENTE', 'ASIGNADA', '2026-07-22 16:41:49', 1),
(161, '019f8a16-aaaa-7152-bd8b-30b3a85ce4bb', 'PAGADA', 'FINALIZADA', '2026-07-22 16:41:59', 2),
(162, '019f8ab3-9ed7-7133-8d0f-2c6803585017', 'ASIGNADA', 'COTIZADA', '2026-07-22 16:47:03', 2),
(163, '019f8ab3-3cc3-707c-a442-52549bb86c48', 'ASIGNADA', 'COTIZADA', '2026-07-22 16:47:18', 2),
(164, '019f8ab3-3cc3-707c-a442-52549bb86c48', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 16:48:14', 3),
(165, '019f8ab3-9ed7-7133-8d0f-2c6803585017', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 16:49:04', 3),
(166, '019f8ab3-3cc3-707c-a442-52549bb86c48', 'REVISION_PAGO', 'APROBADA', '2026-07-22 16:52:59', 1),
(167, '019f8ab3-9ed7-7133-8d0f-2c6803585017', 'REVISION_PAGO', 'PAGADA', '2026-07-22 16:53:02', 1),
(168, '019f8ab3-9ed7-7133-8d0f-2c6803585017', 'PAGADA', 'FINALIZADA', '2026-07-22 16:54:19', 2),
(169, '019f8ab3-3cc3-707c-a442-52549bb86c48', 'APROBADA', 'EN_PROCESO', '2026-07-22 16:54:38', 2),
(170, '019f8ab3-3cc3-707c-a442-52549bb86c48', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-22 16:57:41', 3),
(171, '019f8ab3-3cc3-707c-a442-52549bb86c48', 'REVISION_PAGO', 'PAGADA', '2026-07-22 17:02:13', 1),
(172, '019f8ac8-5b1d-73b1-a5f6-1a806b520762', NULL, 'PENDIENTE', '2026-07-22 17:03:34', 3),
(173, '019f8ac8-5b1d-73b1-a5f6-1a806b520762', 'PENDIENTE', 'ASIGNADA', '2026-07-22 17:04:01', 1),
(174, '019f8ac8-5b1d-73b1-a5f6-1a806b520762', 'ASIGNADA', 'COTIZADA', '2026-07-22 17:04:14', 2),
(175, '019f8ac8-5b1d-73b1-a5f6-1a806b520762', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 17:05:26', 3),
(176, '019f8ac8-5b1d-73b1-a5f6-1a806b520762', 'REVISION_PAGO', 'APROBADA', '2026-07-22 17:06:26', 1),
(177, '019f8ab3-3cc3-707c-a442-52549bb86c48', 'PAGADA', 'FINALIZADA', '2026-07-22 17:06:31', 2),
(178, '019f8ac8-5b1d-73b1-a5f6-1a806b520762', 'APROBADA', 'EN_PROCESO', '2026-07-22 17:07:00', 2),
(179, '019f8ac8-5b1d-73b1-a5f6-1a806b520762', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-22 17:08:31', 3),
(180, '019f8ac8-5b1d-73b1-a5f6-1a806b520762', 'REVISION_PAGO', 'PAGADA', '2026-07-22 17:08:52', 1),
(181, '019f8ac8-5b1d-73b1-a5f6-1a806b520762', 'PAGADA', 'FINALIZADA', '2026-07-22 17:09:36', 2),
(182, '019f8ad0-81f0-7128-989b-2b965a3d98ac', NULL, 'PENDIENTE', '2026-07-22 17:12:28', 3),
(183, '019f85c3-be5c-7360-b762-7c92cdd2fb38', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 17:13:16', 3),
(184, '019f85c3-be5c-7360-b762-7c92cdd2fb38', 'REVISION_PAGO', 'APROBADA', '2026-07-22 17:14:04', 1),
(185, '019f85c3-be5c-7360-b762-7c92cdd2fb38', 'APROBADA', 'EN_PROCESO', '2026-07-22 17:18:02', 2),
(186, '019f85c3-be5c-7360-b762-7c92cdd2fb38', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-22 17:18:19', 3),
(187, '019f85c3-be5c-7360-b762-7c92cdd2fb38', 'REVISION_PAGO', 'PAGADA', '2026-07-22 17:18:51', 1),
(188, '019f85c3-be5c-7360-b762-7c92cdd2fb38', 'PAGADA', 'FINALIZADA', '2026-07-22 17:19:44', 2),
(189, '019f8ad0-81f0-7128-989b-2b965a3d98ac', 'PENDIENTE', 'ASIGNADA', '2026-07-22 17:29:37', 1),
(190, '019f8ad0-81f0-7128-989b-2b965a3d98ac', 'ASIGNADA', 'COTIZADA', '2026-07-22 17:29:56', 2),
(191, '019f8ad0-81f0-7128-989b-2b965a3d98ac', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 17:30:19', 3),
(192, '019f8ad0-81f0-7128-989b-2b965a3d98ac', 'REVISION_PAGO', 'APROBADA', '2026-07-22 17:31:22', 1),
(193, '019f8ad0-81f0-7128-989b-2b965a3d98ac', 'APROBADA', 'EN_PROCESO', '2026-07-22 17:32:17', 2),
(194, '019f8ad0-81f0-7128-989b-2b965a3d98ac', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-22 17:33:08', 3),
(195, '019f8ad0-81f0-7128-989b-2b965a3d98ac', 'REVISION_PAGO', 'PAGADA', '2026-07-22 17:33:33', 1),
(196, '019f8ad0-81f0-7128-989b-2b965a3d98ac', 'PAGADA', 'FINALIZADA', '2026-07-22 17:34:14', 2),
(197, '019f6e0b-56eb-737e-ae10-cf9781a6c4d3', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 17:40:08', 3),
(198, '019f8ab0-dd5d-7272-8186-74d40aa90cd6', 'PENDIENTE', 'ASIGNADA', '2026-07-22 17:40:30', 1),
(199, '019f8ab0-dd5d-7272-8186-74d40aa90cd6', 'ASIGNADA', 'COTIZADA', '2026-07-22 17:41:05', 2),
(200, '019f6e0b-56eb-737e-ae10-cf9781a6c4d3', 'REVISION_PAGO', 'APROBADA', '2026-07-22 17:41:41', 1),
(201, '019f6e0b-56eb-737e-ae10-cf9781a6c4d3', 'APROBADA', 'EN_PROCESO', '2026-07-22 17:42:30', 2),
(202, '019f6e0b-56eb-737e-ae10-cf9781a6c4d3', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-22 17:42:50', 3),
(203, '019f6e0b-56eb-737e-ae10-cf9781a6c4d3', 'REVISION_PAGO', 'PAGADA', '2026-07-22 17:43:10', 1),
(204, '019f6e0b-56eb-737e-ae10-cf9781a6c4d3', 'PAGADA', 'FINALIZADA', '2026-07-22 17:43:33', 2),
(205, '019f8aee-8b36-714c-9868-af4614836584', NULL, 'PENDIENTE', '2026-07-22 17:45:16', 3),
(206, '019f8aee-8b36-714c-9868-af4614836584', 'PENDIENTE', 'ASIGNADA', '2026-07-22 17:45:51', 1),
(207, '019f8aee-8b36-714c-9868-af4614836584', 'ASIGNADA', 'COTIZADA', '2026-07-22 17:46:13', 2),
(208, '019f8aee-8b36-714c-9868-af4614836584', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 17:46:43', 3),
(209, '019f8aee-8b36-714c-9868-af4614836584', 'REVISION_PAGO', 'PAGADA', '2026-07-22 17:47:38', 1),
(210, '019f8af2-59d7-7004-9828-ec5bd5bfba32', NULL, 'PENDIENTE', '2026-07-22 17:49:26', 3),
(211, '019f8af2-59d7-7004-9828-ec5bd5bfba32', 'PENDIENTE', 'ASIGNADA', '2026-07-22 17:49:59', 1),
(212, '019f8af2-59d7-7004-9828-ec5bd5bfba32', 'ASIGNADA', 'COTIZADA', '2026-07-22 17:50:30', 2),
(213, '019f8af2-59d7-7004-9828-ec5bd5bfba32', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 17:51:11', 3),
(214, '019f8af2-59d7-7004-9828-ec5bd5bfba32', 'REVISION_PAGO', 'PAGADA', '2026-07-22 17:52:10', 1),
(215, '019f8afd-33c1-70ce-a9c9-ee24f601b048', NULL, 'PENDIENTE', '2026-07-22 18:01:17', 3),
(216, '019f8afd-7f4a-7026-aa37-75c192d4c044', NULL, 'PENDIENTE', '2026-07-22 18:01:36', 3),
(217, '019f8afd-7f4a-7026-aa37-75c192d4c044', 'PENDIENTE', 'ASIGNADA', '2026-07-22 18:04:25', 1),
(218, '019f8afd-33c1-70ce-a9c9-ee24f601b048', 'PENDIENTE', 'ASIGNADA', '2026-07-22 18:04:44', 1),
(219, '019f8b01-7950-7146-91dc-433dc11250ae', NULL, 'PENDIENTE', '2026-07-22 18:05:57', 3),
(220, '019f8b01-b8b5-7060-a859-70f8d2ac6042', NULL, 'PENDIENTE', '2026-07-22 18:06:13', 3),
(221, '019f8b01-7950-7146-91dc-433dc11250ae', 'PENDIENTE', 'ASIGNADA', '2026-07-22 18:07:07', 1),
(222, '019f8b01-b8b5-7060-a859-70f8d2ac6042', 'PENDIENTE', 'ASIGNADA', '2026-07-22 18:07:40', 1),
(223, '019f8b01-b8b5-7060-a859-70f8d2ac6042', 'ASIGNADA', 'COTIZADA', '2026-07-22 18:10:17', 2),
(224, '019f8b01-7950-7146-91dc-433dc11250ae', 'ASIGNADA', 'COTIZADA', '2026-07-22 18:10:27', 2),
(225, '019f8afd-33c1-70ce-a9c9-ee24f601b048', 'ASIGNADA', 'COTIZADA', '2026-07-22 18:10:38', 2),
(226, '019f8b01-7950-7146-91dc-433dc11250ae', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 18:11:38', 3),
(227, '019f8b01-7950-7146-91dc-433dc11250ae', 'REVISION_PAGO', 'APROBADA', '2026-07-22 18:12:02', 1),
(228, '019f8b01-b8b5-7060-a859-70f8d2ac6042', 'COTIZADA', 'REVISION_PAGO', '2026-07-22 18:12:17', 3),
(229, '019f8b01-7950-7146-91dc-433dc11250ae', 'APROBADA', 'EN_PROCESO', '2026-07-22 18:12:18', 2),
(230, '019f8b01-b8b5-7060-a859-70f8d2ac6042', 'REVISION_PAGO', 'PAGADA', '2026-07-22 18:13:19', 1),
(231, '019f8b01-7950-7146-91dc-433dc11250ae', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-22 18:13:50', 3),
(232, '019f8b01-7950-7146-91dc-433dc11250ae', 'REVISION_PAGO', 'PAGADA', '2026-07-22 18:13:59', 1),
(233, '019f8b01-b8b5-7060-a859-70f8d2ac6042', 'PAGADA', 'FINALIZADA', '2026-07-23 14:58:03', 2),
(234, '019f8b01-7950-7146-91dc-433dc11250ae', 'PAGADA', 'FINALIZADA', '2026-07-23 14:58:41', 2),
(235, '019f8af2-59d7-7004-9828-ec5bd5bfba32', 'PAGADA', 'FINALIZADA', '2026-07-23 15:00:50', 2),
(236, '019f8aee-8b36-714c-9868-af4614836584', 'PAGADA', 'FINALIZADA', '2026-07-23 15:09:17', 2),
(237, '019f8f86-1ce0-7159-b91d-912c430ff512', NULL, 'PENDIENTE', '2026-07-23 15:09:18', 3),
(238, '019f8f86-63de-714d-9b70-6683256467a9', NULL, 'PENDIENTE', '2026-07-23 15:09:37', 3),
(239, '019f8f86-63de-714d-9b70-6683256467a9', 'PENDIENTE', 'ASIGNADA', '2026-07-23 15:25:46', 1),
(240, '019f8f86-63de-714d-9b70-6683256467a9', 'ASIGNADA', 'COTIZADA', '2026-07-23 15:26:07', 2),
(241, '019f8f86-1ce0-7159-b91d-912c430ff512', 'PENDIENTE', 'ASIGNADA', '2026-07-23 15:26:36', 1),
(242, '019f8f86-63de-714d-9b70-6683256467a9', 'COTIZADA', 'REVISION_PAGO', '2026-07-23 15:29:48', 3),
(243, '019f8f86-1ce0-7159-b91d-912c430ff512', 'ASIGNADA', 'COTIZADA', '2026-07-23 15:30:45', 2),
(244, '019f8f86-63de-714d-9b70-6683256467a9', 'REVISION_PAGO', 'PAGADA', '2026-07-23 15:30:58', 1),
(245, '019f8f86-63de-714d-9b70-6683256467a9', 'PAGADA', 'FINALIZADA', '2026-07-23 15:31:58', 2),
(246, '019f8f86-1ce0-7159-b91d-912c430ff512', 'COTIZADA', 'REVISION_PAGO', '2026-07-23 15:32:40', 3),
(247, '019f8f86-1ce0-7159-b91d-912c430ff512', 'REVISION_PAGO', 'APROBADA', '2026-07-23 15:33:15', 1),
(248, '019f8f86-1ce0-7159-b91d-912c430ff512', 'APROBADA', 'EN_PROCESO', '2026-07-23 15:33:55', 2),
(249, '019f8f86-1ce0-7159-b91d-912c430ff512', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-23 15:34:11', 3),
(250, '019f8f86-1ce0-7159-b91d-912c430ff512', 'REVISION_PAGO', 'PAGADA', '2026-07-23 15:34:20', 1),
(251, '019f8f86-1ce0-7159-b91d-912c430ff512', 'PAGADA', 'FINALIZADA', '2026-07-23 15:34:45', 2),
(252, '019f8fae-f51f-734c-85ce-9f231354d96e', NULL, 'PENDIENTE', '2026-07-23 15:53:55', 3),
(253, '019f8faf-cefc-7317-94a7-7aa2708626ad', NULL, 'PENDIENTE', '2026-07-23 15:54:51', 7),
(254, '019f8fb0-d671-7311-a896-8dffb0622375', NULL, 'PENDIENTE', '2026-07-23 15:55:58', 6),
(255, '019f8fae-f51f-734c-85ce-9f231354d96e', 'PENDIENTE', 'ASIGNADA', '2026-07-23 15:56:10', 1),
(257, '019f8fae-f51f-734c-85ce-9f231354d96e', 'ASIGNADA', 'COTIZADA', '2026-07-23 15:59:04', 2),
(259, '019f8fb0-d671-7311-a896-8dffb0622375', 'PENDIENTE', 'ASIGNADA', '2026-07-23 15:59:53', 1),
(260, '019f8faf-cefc-7317-94a7-7aa2708626ad', 'PENDIENTE', 'ASIGNADA', '2026-07-23 16:00:05', 1),
(262, '019f8fae-f51f-734c-85ce-9f231354d96e', 'COTIZADA', 'REVISION_PAGO', '2026-07-23 16:04:18', 3),
(263, '019f8fae-f51f-734c-85ce-9f231354d96e', 'REVISION_PAGO', 'APROBADA', '2026-07-23 16:05:49', 1),
(264, '019f8fae-f51f-734c-85ce-9f231354d96e', 'APROBADA', 'EN_PROCESO', '2026-07-23 16:07:48', 2),
(265, '019f8fae-f51f-734c-85ce-9f231354d96e', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-23 16:08:46', 3),
(266, '019f8fae-f51f-734c-85ce-9f231354d96e', 'REVISION_PAGO', 'PAGADA', '2026-07-23 16:09:02', 1),
(267, '019f8fae-f51f-734c-85ce-9f231354d96e', 'PAGADA', 'FINALIZADA', '2026-07-23 16:17:12', 2),
(268, '019fb888-962f-7202-84ae-28f2e94b74e2', NULL, 'PENDIENTE', '2026-07-31 14:16:26', 3),
(269, '019fb888-962f-7202-84ae-28f2e94b74e2', 'PENDIENTE', 'ASIGNADA', '2026-07-31 14:34:56', 1),
(270, '019f8afd-7f4a-7026-aa37-75c192d4c044', 'ASIGNADA', 'COTIZADA', '2026-07-31 14:38:40', 4),
(271, '019fb888-962f-7202-84ae-28f2e94b74e2', 'ASIGNADA', 'COTIZADA', '2026-07-31 14:39:18', 4),
(272, '019fb888-962f-7202-84ae-28f2e94b74e2', 'COTIZADA', 'REVISION_PAGO', '2026-07-31 14:39:40', 3),
(273, '019fb888-962f-7202-84ae-28f2e94b74e2', 'REVISION_PAGO', 'COTIZADA', '2026-07-31 14:40:01', 1),
(274, '019fb8ad-c653-7347-85de-8bebae26f5b3', NULL, 'PENDIENTE', '2026-07-31 14:57:04', 3),
(275, '019fb8ad-c653-7347-85de-8bebae26f5b3', 'PENDIENTE', 'ASIGNADA', '2026-07-31 15:08:53', 1),
(276, '019fb8ad-c653-7347-85de-8bebae26f5b3', 'ASIGNADA', 'COTIZADA', '2026-07-31 15:09:08', 2),
(277, '019fb8ad-c653-7347-85de-8bebae26f5b3', 'COTIZADA', 'REVISION_PAGO', '2026-07-31 15:09:51', 3),
(278, '019fb8ad-c653-7347-85de-8bebae26f5b3', 'REVISION_PAGO', 'APROBADA', '2026-07-31 15:12:54', 1),
(279, '019fb8ad-c653-7347-85de-8bebae26f5b3', 'APROBADA', 'EN_PROCESO', '2026-07-31 15:14:35', 2),
(280, '019fb8ad-c653-7347-85de-8bebae26f5b3', 'EN_PROCESO', 'REVISION_PAGO', '2026-07-31 15:19:46', 3),
(281, '019fb8ad-c653-7347-85de-8bebae26f5b3', 'REVISION_PAGO', 'PAGADA', '2026-07-31 15:22:11', 1),
(282, '019fb8ad-c653-7347-85de-8bebae26f5b3', 'PAGADA', 'FINALIZADA', '2026-07-31 15:23:05', 2),
(283, '019fb8ca-81c7-72a3-9556-e843ae93f217', NULL, 'PENDIENTE', '2026-07-31 15:28:27', 3),
(284, '019fb8ca-81c7-72a3-9556-e843ae93f217', 'PENDIENTE', 'ASIGNADA', '2026-07-31 15:29:39', 1),
(285, '019fb8ca-81c7-72a3-9556-e843ae93f217', 'ASIGNADA', 'COTIZADA', '2026-07-31 15:32:13', 2),
(286, '019fb8ca-81c7-72a3-9556-e843ae93f217', 'COTIZADA', 'REVISION_PAGO', '2026-07-31 15:33:03', 3),
(287, '019fb8ca-81c7-72a3-9556-e843ae93f217', 'REVISION_PAGO', 'PAGADA', '2026-07-31 15:33:45', 1),
(288, '019fb8ca-81c7-72a3-9556-e843ae93f217', 'PAGADA', 'FINALIZADA', '2026-07-31 15:35:10', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `items_catalogo`
--

CREATE TABLE `items_catalogo` (
  `id_item` bigint(20) UNSIGNED NOT NULL,
  `sku_codigo` varchar(50) DEFAULT NULL,
  `tipo_item` enum('MATERIAL','SERVICIO') NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `unidad_medida` varchar(20) DEFAULT NULL,
  `precio_ref` decimal(10,2) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ;

--
-- Volcado de datos para la tabla `items_catalogo`
--

INSERT INTO `items_catalogo` (`id_item`, `sku_codigo`, `tipo_item`, `nombre`, `descripcion`, `unidad_medida`, `precio_ref`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'MAT001', 'MATERIAL', 'Cable THW 2.5mm', 'Cable de cobre THW calibre 2.5mm, uso residencial', 'metro', 2.50, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(2, 'MAT002', 'MATERIAL', 'Cable THW 4.0mm', 'Cable de cobre THW calibre 4.0mm', 'metro', 4.00, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(3, 'MAT003', 'MATERIAL', 'Breaker 20A Bipolar', 'Interruptor termomagnético 20 amperios bipolar', 'unidad', 35.00, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(4, 'MAT004', 'MATERIAL', 'Breaker 30A Bipolar', 'Interruptor termomagnético 30 amperios bipolar', 'unidad', 42.00, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(5, 'MAT005', 'MATERIAL', 'Tablero 8 Polos', 'Tablero de distribución 8 polos con barra de tierra', 'unidad', 280.00, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(6, 'MAT006', 'MATERIAL', 'Tablero 12 Polos', 'Tablero de distribución 12 polos con barra de tierra', 'unidad', 350.00, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(7, 'MAT007', 'MATERIAL', 'Tomacorriente Doble', 'Tomacorriente doble polarizado con placa decorativa', 'unidad', 8.50, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(8, 'MAT008', 'MATERIAL', 'Interruptor Simple', 'Interruptor simple con placa decorativa blanca', 'unidad', 6.00, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(9, 'MAT009', 'MATERIAL', 'Foco LED 9W', 'Foco LED 9W luz blanca 6500K', 'unidad', 12.00, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(10, 'MAT010', 'MATERIAL', 'Foco LED 15W', 'Foco LED 15W luz blanca 6500K', 'unidad', 18.00, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(11, 'MAT011', 'MATERIAL', 'Caja de Derivación', 'Caja de derivación rectangular PVC 4x4', 'unidad', 6.50, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(12, 'MAT012', 'MATERIAL', 'Tubería Conduit 3/4', 'Tubería PVC conduit rígida 3/4 pulgadas', 'tramo', 12.00, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(13, 'MAT013', 'MATERIAL', 'Cinta Aislante 3M', 'Cinta aislante eléctrica negra 18 metros', 'rollo', 4.50, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(14, 'MAT014', 'MATERIAL', 'Canalización 20mm', 'Canaleta PVC autoadhesiva 20mm x 2 metros', 'tramo', 8.00, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(15, 'SRV001', 'SERVICIO', 'Mano de Obra: Instalación', 'Servicio de instalación eléctrica completa', 'servicio', 500.00, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(16, 'SRV002', 'SERVICIO', 'Mantenimiento Preventivo', 'Revisión y mantenimiento preventivo de instalaciones', 'servicio', 120.00, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(17, 'SRV003', 'SERVICIO', 'Diagnóstico Eléctrico', 'Evaluación completa del sistema eléctrico con informe', 'servicio', 80.00, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(18, 'SRV004', 'SERVICIO', 'Reparación de Cortocircuito', 'Diagnóstico y reparación de fallas eléctricas', 'servicio', 250.00, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(19, 'SRV005', 'SERVICIO', 'Instalación de Punto Eléctrico', 'Instalación de tomacorriente o interruptor', 'punto', 35.00, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44'),
(20, 'SRV006', 'SERVICIO', 'Recableado Completo', 'Reemplazo total de cableado en ambiente', 'servicio', 800.00, 1, '2026-07-16 17:45:44', '2026-07-16 17:45:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_06_08_133801_create_personal_access_tokens_table', 1),
(2, '2026_06_08_140400_create_roles_table', 1),
(3, '2026_06_08_141022_create_usuarios_table', 1),
(4, '2026_06_08_141031_create_perfiles_admin_table', 1),
(5, '2026_06_08_141036_create_perfiles_tecnicos_table', 1),
(6, '2026_06_08_141042_create_perfiles_clientes_table', 1),
(7, '2026_06_08_141047_create_items_catalogo_table', 1),
(8, '2026_06_08_141052_create_solicitudes_table', 1),
(9, '2026_06_08_141057_create_historial_estados_table', 1),
(10, '2026_06_08_141102_create_cotizaciones_table', 1),
(11, '2026_06_08_141111_create_detalle_cotizacion_table', 1),
(12, '2026_06_08_141116_create_evidencias_table', 1),
(13, '2026_06_08_141121_create_pagos_table', 1),
(14, '2026_06_08_141126_create_vistas_y_procedimientos', 1),
(15, '2026_06_12_052657_create_procedures_and_triggers', 1),
(16, '2026_07_08_041023_create_tipos_trabajo_table', 1),
(17, '2026_07_08_041426_create_tipo_trabajo_items_sugeridos_table', 1),
(18, '2026_07_10_032857_add_ubicacion_to_solicitudes_table', 1),
(19, '2026_07_17_011251_create_tipo_trabajo_items_feedback_table', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` bigint(20) UNSIGNED NOT NULL,
  `uuid_solicitud` char(36) NOT NULL,
  `id_usuario_registro` bigint(20) UNSIGNED NOT NULL,
  `monto_pagado` decimal(12,2) NOT NULL,
  `metodo_pago` enum('EFECTIVO','YAPE','PLIN','TRANSFERENCIA','TARJETA') NOT NULL,
  `nro_operacion` varchar(100) DEFAULT NULL,
  `estado_pago` enum('PENDIENTE_APROBACION','COMPLETADO','RECHAZADO','REEMBOLSADO') NOT NULL DEFAULT 'PENDIENTE_APROBACION',
  `url_comprobante` varchar(500) DEFAULT NULL,
  `tipo_pago` enum('ADELANTO','FINAL') NOT NULL DEFAULT 'FINAL',
  `id_usuario_aprobacion` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha_aprobacion` timestamp NULL DEFAULT NULL,
  `fecha_pago` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id_pago`, `uuid_solicitud`, `id_usuario_registro`, `monto_pagado`, `metodo_pago`, `nro_operacion`, `estado_pago`, `url_comprobante`, `tipo_pago`, `id_usuario_aprobacion`, `fecha_aprobacion`, `fecha_pago`, `deleted_at`) VALUES
(1, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 3, 168.15, 'TRANSFERENCIA', '123456789', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784224594/sigesto/comprobantes/rpdwgqpi8afufo3rdn10.png', 'ADELANTO', 1, '2026-07-16 18:03:47', '2026-07-16 17:56:34', NULL),
(2, '019f6c0e-2554-70ed-94ed-4d1ba8283952', 3, 177.00, 'TRANSFERENCIA', '123456789', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784224626/sigesto/comprobantes/xntcesciwowoqijv2jhr.png', 'FINAL', 1, '2026-07-16 18:03:43', '2026-07-16 17:57:06', NULL),
(3, '019f6c0d-8618-728c-9bfe-d537c0a80b81', 2, 168.15, 'TRANSFERENCIA', '123', 'COMPLETADO', NULL, 'FINAL', NULL, NULL, '2026-07-16 18:04:51', NULL),
(4, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 3, 67.85, 'TRANSFERENCIA', '123456', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784257638/sigesto/comprobantes/bmsuzsxfifihs0rbilim.png', 'ADELANTO', 1, '2026-07-17 03:17:31', '2026-07-17 03:07:18', NULL),
(5, '019f6d2a-f251-708d-92bf-c27d9f51cf00', 3, 67.85, 'TRANSFERENCIA', '12345668', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784286605/sigesto/comprobantes/r3fhqfnk1aq38t7fijhd.png', 'FINAL', 1, '2026-07-17 14:28:13', '2026-07-17 11:10:05', NULL),
(6, '019f7034-c3a5-73ef-988f-69086a7b34f7', 3, 67.85, 'TRANSFERENCIA', '15263637', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784297215/sigesto/comprobantes/qhjeeb6yzwexjnc7kzhb.png', 'ADELANTO', 1, '2026-07-17 14:07:50', '2026-07-17 14:06:55', NULL),
(7, '019f7034-c3a5-73ef-988f-69086a7b34f7', 3, 67.85, 'TRANSFERENCIA', '99999999', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784298537/sigesto/comprobantes/pgehthkwhf6t8p5nqdxk.png', 'FINAL', 1, '2026-07-17 14:29:31', '2026-07-17 14:28:58', NULL),
(8, '019f7c61-5df2-7290-9a72-9b5e58440eb4', 3, 226.56, 'TRANSFERENCIA', '2345678', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784516524/sigesto/comprobantes/xwb1yyoxwhegb7rdasuz.png', 'FINAL', 1, '2026-07-20 03:11:55', '2026-07-20 03:02:05', NULL),
(9, '019f78af-d196-7192-b9d3-e1f3386c0913', 3, 67.85, 'TRANSFERENCIA', '12345678', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784516608/sigesto/comprobantes/f0xalds80ctazh8l24ob.png', 'ADELANTO', 1, '2026-07-20 03:11:46', '2026-07-20 03:03:29', NULL),
(10, '019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 3, 73.45, 'TRANSFERENCIA', '123456789', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784651882/sigesto/comprobantes/bqx8z2bl95ys2igkurnd.png', 'ADELANTO', 1, '2026-07-21 18:00:58', '2026-07-21 16:38:02', NULL),
(12, '019f78af-d196-7192-b9d3-e1f3386c0913', 3, 67.85, 'TRANSFERENCIA', '123456789', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784656637/sigesto/comprobantes/yt6jecmdvqgwmyj7mkmo.png', 'FINAL', 1, '2026-07-21 18:01:07', '2026-07-21 17:57:18', NULL),
(13, '019f8a16-aaaa-7152-bd8b-30b3a85ce4bb', 3, 480.85, 'TRANSFERENCIA', '123456789', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784729283/sigesto/comprobantes/xmbxwgsguhn3xgnhykma.png', 'ADELANTO', 1, '2026-07-22 14:09:57', '2026-07-22 14:08:03', NULL),
(14, '019f8a16-aaaa-7152-bd8b-30b3a85ce4bb', 3, 480.85, 'TRANSFERENCIA', '123456789', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784729557/sigesto/comprobantes/uwsq2xqbx2uad0l4spha.png', 'FINAL', 1, '2026-07-22 14:13:22', '2026-07-22 14:12:37', NULL),
(15, '019f8a64-e3b6-70ba-a944-ad919090b49b', 3, 473.48, 'TRANSFERENCIA', '123456789', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784733453/sigesto/comprobantes/jbrwt6vfjlmfvxguo64k.png', 'ADELANTO', 1, '2026-07-22 15:18:05', '2026-07-22 15:17:33', NULL),
(16, '019f8a64-e3b6-70ba-a944-ad919090b49b', 3, 473.47, 'TRANSFERENCIA', '999999999999', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784733547/sigesto/comprobantes/zqwqsvbllhpwdyblkmri.png', 'FINAL', 1, '2026-07-22 15:19:30', '2026-07-22 15:19:08', NULL),
(17, '019f8a72-1c8e-72e5-a4ff-742648fb5881', 3, 23.30, 'TRANSFERENCIA', '987654321', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784734232/sigesto/comprobantes/ewkri3xgdsaruxei8lj3.png', 'ADELANTO', 1, '2026-07-22 15:30:56', '2026-07-22 15:30:33', NULL),
(18, '019f8a72-1c8e-72e5-a4ff-742648fb5881', 3, 23.31, 'TRANSFERENCIA', '88888888', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784734313/sigesto/comprobantes/xbjxsmqgmywp1opzkkrq.png', 'FINAL', 1, '2026-07-22 15:32:15', '2026-07-22 15:31:54', NULL),
(19, '019f8a77-6830-70c5-bfc9-b2c59abc1254', 3, 300.31, 'TRANSFERENCIA', '666666', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784734561/sigesto/comprobantes/r8igs6cogegwt4u9fvnp.png', 'FINAL', 1, '2026-07-22 15:36:29', '2026-07-22 15:36:01', NULL),
(20, '019f8a81-d9ad-71da-b4d0-453bf061abcf', 3, 178.59, 'TRANSFERENCIA', '5555555', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784735358/sigesto/comprobantes/cejfzo7jmibeovzhjtbd.png', 'ADELANTO', 1, '2026-07-22 15:49:36', '2026-07-22 15:49:19', NULL),
(21, '019f8a81-d9ad-71da-b4d0-453bf061abcf', 3, 416.72, 'TRANSFERENCIA', '22222222222', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784735520/sigesto/comprobantes/pprnowfrnr4yxfu8uz6m.png', 'FINAL', 1, '2026-07-22 15:52:49', '2026-07-22 15:52:00', NULL),
(22, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 3, 23.30, 'TRANSFERENCIA', '55555555', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784736075/sigesto/comprobantes/bq0mqniate0mrm9izrw4.png', 'ADELANTO', 1, '2026-07-22 16:01:50', '2026-07-22 16:01:16', NULL),
(23, '019f8a8c-a04d-7355-9393-a5c2ccc05406', 3, 23.31, 'TRANSFERENCIA', '99999999', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784736188/sigesto/comprobantes/xvtutlbr7oonjmu0dduy.png', 'FINAL', 1, '2026-07-22 16:03:35', '2026-07-22 16:03:08', NULL),
(24, '019f8a94-f17d-736f-8c8e-ea6e6833cd65', 3, 73.45, 'TRANSFERENCIA', '6666666', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784736880/sigesto/comprobantes/a2dlonsqex7ozpfbqtbj.png', 'ADELANTO', 1, '2026-07-22 16:16:02', '2026-07-22 16:14:40', NULL),
(25, '019f8a94-f17d-736f-8c8e-ea6e6833cd65', 3, 73.46, 'TRANSFERENCIA', '22222222', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784737067/sigesto/comprobantes/xyshhodytqmnoojomwl3.png', 'FINAL', 1, '2026-07-22 16:18:24', '2026-07-22 16:17:48', NULL),
(26, '019f8a9f-eb47-7132-93d3-44f31997cf4d', 3, 23.30, 'TRANSFERENCIA', '8888888', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784737244/sigesto/comprobantes/mkbvz9was2dbzwz7otlx.png', 'ADELANTO', 1, '2026-07-22 16:21:07', '2026-07-22 16:20:44', NULL),
(27, '019f8a9f-eb47-7132-93d3-44f31997cf4d', 3, 23.31, 'TRANSFERENCIA', '66666666', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784737307/sigesto/comprobantes/q7ho5dm3xqucyqohklgi.png', 'FINAL', 1, '2026-07-22 16:22:08', '2026-07-22 16:21:47', NULL),
(28, '019f8aa4-d04e-715a-b720-c33e14ad350d', 3, 73.45, 'TRANSFERENCIA', '33333333', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784737727/sigesto/comprobantes/xh8ytq4e2ij5z222rphy.png', 'ADELANTO', 1, '2026-07-22 16:29:11', '2026-07-22 16:28:47', NULL),
(29, '019f8aa4-d04e-715a-b720-c33e14ad350d', 3, 73.46, 'TRANSFERENCIA', '66666666666666666', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784737848/sigesto/comprobantes/yltwx4r4tysdfqmexgam.png', 'FINAL', 1, '2026-07-22 16:39:24', '2026-07-22 16:30:49', NULL),
(30, '019f8ab3-3cc3-707c-a442-52549bb86c48', 3, 73.45, 'TRANSFERENCIA', '6666666666', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784738893/sigesto/comprobantes/w4skzegu2njpxzt80pnj.png', 'ADELANTO', 1, '2026-07-22 16:52:59', '2026-07-22 16:48:14', NULL),
(31, '019f8ab3-9ed7-7133-8d0f-2c6803585017', 3, 595.31, 'TRANSFERENCIA', '33333333333333', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784738943/sigesto/comprobantes/tujrx7dk7i1ufdd99rkj.png', 'FINAL', 1, '2026-07-22 16:53:02', '2026-07-22 16:49:04', NULL),
(32, '019f8ab3-3cc3-707c-a442-52549bb86c48', 3, 73.46, 'TRANSFERENCIA', '6666666666', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784739461/sigesto/comprobantes/bkw8qa0bzp7mvslfamk2.png', 'FINAL', 1, '2026-07-22 17:02:13', '2026-07-22 16:57:41', NULL),
(33, '019f8ac8-5b1d-73b1-a5f6-1a806b520762', 3, 297.65, 'TRANSFERENCIA', '1111111111111', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784739926/sigesto/comprobantes/xo52byrrycektnvpiqzx.png', 'ADELANTO', 1, '2026-07-22 17:06:26', '2026-07-22 17:05:26', NULL),
(34, '019f8ac8-5b1d-73b1-a5f6-1a806b520762', 3, 297.66, 'TRANSFERENCIA', '321321321', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784740111/sigesto/comprobantes/mqlr28syuhs47sunjec8.png', 'FINAL', 1, '2026-07-22 17:08:52', '2026-07-22 17:08:31', NULL),
(35, '019f85c3-be5c-7360-b762-7c92cdd2fb38', 3, 73.45, 'TRANSFERENCIA', '6666666666666', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784740395/sigesto/comprobantes/msekupxtf0hizmqvafec.png', 'ADELANTO', 1, '2026-07-22 17:14:04', '2026-07-22 17:13:16', NULL),
(36, '019f85c3-be5c-7360-b762-7c92cdd2fb38', 3, 73.46, 'TRANSFERENCIA', '123123123123', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784740699/sigesto/comprobantes/c3qnsokamskrzlvfhcte.png', 'FINAL', 1, '2026-07-22 17:18:51', '2026-07-22 17:18:19', NULL),
(37, '019f8ad0-81f0-7128-989b-2b965a3d98ac', 3, 73.45, 'TRANSFERENCIA', '3333333', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784741418/sigesto/comprobantes/ak971opfuddypuf1xmri.png', 'ADELANTO', 1, '2026-07-22 17:31:22', '2026-07-22 17:30:19', NULL),
(38, '019f8ad0-81f0-7128-989b-2b965a3d98ac', 3, 73.46, 'TRANSFERENCIA', '1111111111111111', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784741588/sigesto/comprobantes/bgutlnkyol7nzahavjrm.png', 'FINAL', 1, '2026-07-22 17:33:33', '2026-07-22 17:33:08', NULL),
(39, '019f6e0b-56eb-737e-ae10-cf9781a6c4d3', 3, 69.33, 'TRANSFERENCIA', '11111111111', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784742007/sigesto/comprobantes/r7namsmdahtkz99ayajv.png', 'ADELANTO', 1, '2026-07-22 17:41:41', '2026-07-22 17:40:08', NULL),
(40, '019f6e0b-56eb-737e-ae10-cf9781a6c4d3', 3, 69.32, 'TRANSFERENCIA', '333333', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784742169/sigesto/comprobantes/kosky3r7jngpt2pqmvfl.png', 'FINAL', 1, '2026-07-22 17:43:10', '2026-07-22 17:42:50', NULL),
(41, '019f8aee-8b36-714c-9868-af4614836584', 3, 46.61, 'TRANSFERENCIA', '6666666666666666', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784742402/sigesto/comprobantes/mswavg9olxpa8muaprxr.png', 'FINAL', 1, '2026-07-22 17:47:38', '2026-07-22 17:46:43', NULL),
(42, '019f8af2-59d7-7004-9828-ec5bd5bfba32', 3, 146.91, 'TRANSFERENCIA', '11111111111111', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784742670/sigesto/comprobantes/whyvohocopy5tecc5wtp.png', 'FINAL', 1, '2026-07-22 17:52:10', '2026-07-22 17:51:11', NULL),
(43, '019f8b01-7950-7146-91dc-433dc11250ae', 3, 300.00, 'TRANSFERENCIA', '123123123', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784743896/sigesto/comprobantes/gtl9qih2lrohgaw3t32d.png', 'ADELANTO', 1, '2026-07-22 18:12:02', '2026-07-22 18:11:38', NULL),
(44, '019f8b01-b8b5-7060-a859-70f8d2ac6042', 3, 146.91, 'TRANSFERENCIA', '11111111111111', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784743936/sigesto/comprobantes/eqxodopfclwifcmmldgj.png', 'FINAL', 1, '2026-07-22 18:13:19', '2026-07-22 18:12:17', NULL),
(45, '019f8b01-7950-7146-91dc-433dc11250ae', 3, 295.31, 'TRANSFERENCIA', '1411111111', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784744030/sigesto/comprobantes/efpygu3m9s5fuxmwdrkv.png', 'FINAL', 1, '2026-07-22 18:13:59', '2026-07-22 18:13:50', NULL),
(46, '019f8f86-63de-714d-9b70-6683256467a9', 3, 48.38, 'TRANSFERENCIA', '123456789', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784820588/sigesto/comprobantes/i3xfohbbppohqjdyptgr.png', 'FINAL', 1, '2026-07-23 15:30:58', '2026-07-23 15:29:48', NULL),
(47, '019f8f86-1ce0-7159-b91d-912c430ff512', 3, 23.30, 'TRANSFERENCIA', '123456789', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784820759/sigesto/comprobantes/muzko1ynikxcz1mp8d9w.png', 'ADELANTO', 1, '2026-07-23 15:33:15', '2026-07-23 15:32:40', NULL),
(48, '019f8f86-1ce0-7159-b91d-912c430ff512', 3, 23.31, 'TRANSFERENCIA', '9999999', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784820851/sigesto/comprobantes/mv0o6d8vjvbisymubqje.png', 'FINAL', 1, '2026-07-23 15:34:20', '2026-07-23 15:34:11', NULL),
(49, '019f8fae-f51f-734c-85ce-9f231354d96e', 3, 29.20, 'TRANSFERENCIA', '123456789', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784822658/sigesto/comprobantes/eequhl3rjm29kz2beqti.png', 'ADELANTO', 1, '2026-07-23 16:05:49', '2026-07-23 16:04:18', NULL),
(50, '019f8fae-f51f-734c-85ce-9f231354d96e', 3, 29.21, 'TRANSFERENCIA', '99999999', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1784822926/sigesto/comprobantes/mfevwlhzovxydydrmwta.png', 'FINAL', 1, '2026-07-23 16:09:02', '2026-07-23 16:08:46', NULL),
(51, '019fb888-962f-7202-84ae-28f2e94b74e2', 3, 48.97, 'TRANSFERENCIA', '1111111111', 'RECHAZADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1785508780/sigesto/comprobantes/f002q4cnkt84wlc7fetn.jpg', 'FINAL', 1, '2026-07-31 14:40:01', '2026-07-31 14:39:40', NULL),
(52, '019fb8ad-c653-7347-85de-8bebae26f5b3', 3, 23.30, 'TRANSFERENCIA', '12345678', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1785510590/sigesto/comprobantes/lap1ytxp6pzapvawnpmf.png', 'ADELANTO', 1, '2026-07-31 15:12:54', '2026-07-31 15:09:51', NULL),
(53, '019fb8ad-c653-7347-85de-8bebae26f5b3', 3, 23.31, 'TRANSFERENCIA', '1111111', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1785511186/sigesto/comprobantes/wzte1t0zbq6pxhcay4mb.jpg', 'FINAL', 1, '2026-07-31 15:22:11', '2026-07-31 15:19:46', NULL),
(54, '019fb8ca-81c7-72a3-9556-e843ae93f217', 3, 46.61, 'TRANSFERENCIA', '11111111111111', 'COMPLETADO', 'https://res.cloudinary.com/dfwocmvri/image/upload/v1785511983/sigesto/comprobantes/qkfiqr2zauvbsjcf7oob.jpg', 'FINAL', 1, '2026-07-31 15:33:45', '2026-07-31 15:33:03', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfiles_admin`
--

CREATE TABLE `perfiles_admin` (
  `id_admin` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `perfiles_admin`
--

INSERT INTO `perfiles_admin` (`id_admin`, `id_usuario`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfiles_clientes`
--

CREATE TABLE `perfiles_clientes` (
  `id_cliente` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `dni_ruc` varchar(20) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `perfiles_clientes`
--

INSERT INTO `perfiles_clientes` (`id_cliente`, `id_usuario`, `dni_ruc`, `telefono`, `direccion`, `deleted_at`) VALUES
(1, 3, '10123456789', '935358929', 'Av. Sol 123, Cusco', NULL),
(2, 5, '73040000', '962458523', 'AV. ff', NULL),
(3, 6, '60844365', '957 146 262', 'Av. Primavera C-8', NULL),
(4, 7, '74453311', '923638823', 'huasao', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfiles_tecnicos`
--

CREATE TABLE `perfiles_tecnicos` (
  `id_tecnico` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `dni` char(8) NOT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `perfiles_tecnicos`
--

INSERT INTO `perfiles_tecnicos` (`id_tecnico`, `id_usuario`, `dni`, `especialidad`, `disponible`, `deleted_at`) VALUES
(1, 2, '12345678', 'Electricidad Residencial', 1, NULL),
(2, 4, '14785236', 'Plomero', 1, NULL),
(3, 8, '60844365', 'electricista', 1, NULL),
(4, 9, '15975312', 'electricista', 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\Usuario', 3, 'sigesto-app', '869bb54cc792816243bcb91af48ef7ff7b9ba5506ae995a01bfc63fddf64f2ea', '[\"*\"]', '2026-07-31 04:09:36', NULL, '2026-07-31 03:56:40', '2026-07-31 04:09:36'),
(2, 'App\\Models\\Usuario', 3, 'sigesto-app', '4637f019e613ba11f2cd53e86b5229eb9f0dac1322a5507ad1d67cdca1b22c33', '[\"*\"]', '2026-07-31 14:24:11', NULL, '2026-07-31 04:00:14', '2026-07-31 14:24:11'),
(4, 'App\\Models\\Usuario', 1, 'sigesto-app', '342b413b4720e88ecea4775103b509c56875384f0a4207ad06c490875a751869', '[\"*\"]', '2026-07-31 14:26:08', NULL, '2026-07-31 14:13:38', '2026-07-31 14:26:08'),
(5, 'App\\Models\\Usuario', 3, 'sigesto-app', '7a3fd93ec3a9e619ff63322ddf7ddeff33f0d07baa0b0e462d189f3d8508804d', '[\"*\"]', '2026-07-31 15:35:39', NULL, '2026-07-31 14:14:30', '2026-07-31 15:35:39'),
(7, 'App\\Models\\Usuario', 1, 'sigesto-app', '5b77015cb8c49b06e31fd5470318f0ed5cfed4cd3b49c8dfbad7ce072e9c775a', '[\"*\"]', '2026-07-31 14:41:39', NULL, '2026-07-31 14:26:48', '2026-07-31 14:41:39'),
(9, 'App\\Models\\Usuario', 2, 'sigesto-app', '963ba1b8d1c00cf4c35ad1922ae72dc1213b6b193ef4441c09234c5b83795dfd', '[\"*\"]', '2026-08-05 13:46:18', NULL, '2026-07-31 14:55:16', '2026-08-05 13:46:18'),
(10, 'App\\Models\\Usuario', 1, 'sigesto-app', '47362259a27e53081ca8225abba18d1cd1e6a781e156cf43fa431eedd8760642', '[\"*\"]', '2026-07-31 15:49:42', NULL, '2026-07-31 15:08:26', '2026-07-31 15:49:42'),
(11, 'App\\Models\\Usuario', 8, 'sigesto-app', 'fc39fbe737e48ae2c639242dc1915cfe3f1012f0f42d9ce13d4c15100ebc37f6', '[\"*\"]', '2026-07-31 15:21:25', NULL, '2026-07-31 15:19:56', '2026-07-31 15:21:25'),
(12, 'App\\Models\\Usuario', 9, 'sigesto-app', '5be77071a91b43dfb8e544d58a639680361d656ab67d018814a668fd003cacbf', '[\"*\"]', '2026-07-31 15:25:14', NULL, '2026-07-31 15:24:33', '2026-07-31 15:25:14'),
(13, 'App\\Models\\Usuario', 4, 'sigesto-app', '50a58a4d446ba546b3d08c2c8cc3dbf38d4da26eac21775363f8c34f35725dfd', '[\"*\"]', '2026-07-31 15:29:59', NULL, '2026-07-31 15:29:57', '2026-07-31 15:29:59'),
(14, 'App\\Models\\Usuario', 1, 'sigesto-app', '193435d29a70193ae50630465a2c61cebedf8f895b792794531193a781607cff', '[\"*\"]', '2026-07-31 16:13:47', NULL, '2026-07-31 16:13:16', '2026-07-31 16:13:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre`) VALUES
(1, 'ADMINISTRADOR'),
(3, 'CLIENTE'),
(2, 'TECNICO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes`
--

CREATE TABLE `solicitudes` (
  `uuid_solicitud` char(36) NOT NULL,
  `id_cliente` bigint(20) UNSIGNED NOT NULL,
  `id_tecnico` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` enum('PENDIENTE','ASIGNADA','COTIZADA','REVISION_PAGO','APROBADA','RECHAZADA','EN_PROCESO','FINALIZADA','PAGADA','CANCELADA') NOT NULL,
  `descripcion_problema` text NOT NULL,
  `direccion_servicio` varchar(255) NOT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `fecha_preferida` date DEFAULT NULL,
  `hora_preferida` time DEFAULT NULL,
  `notas_disponibilidad` varchar(500) DEFAULT NULL,
  `es_urgente` tinyint(1) NOT NULL DEFAULT 0,
  `materiales_cliente` text DEFAULT NULL,
  `fecha_coordinada` date DEFAULT NULL,
  `hora_coordinada` time DEFAULT NULL,
  `notas_coordinacion` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitudes`
--

INSERT INTO `solicitudes` (`uuid_solicitud`, `id_cliente`, `id_tecnico`, `estado`, `descripcion_problema`, `direccion_servicio`, `latitud`, `longitud`, `fecha_preferida`, `hora_preferida`, `notas_disponibilidad`, `es_urgente`, `materiales_cliente`, `fecha_coordinada`, `hora_coordinada`, `notas_coordinacion`, `created_at`, `updated_at`, `deleted_at`) VALUES
('019f6c0d-8618-728c-9bfe-d537c0a80b81', 1, 1, 'FINALIZADA', 'Instalación de un nuevo tomacorriente doble en la sala.', 'Señor De Exehomo, San Sebastián, Cusco, 08006, Perú', -13.53000000, -71.93000000, '2026-07-18', '12:00:00', 'Solo por las mañanas', 0, 'Tengo 15 metros de cable THW.', '2026-07-18', '12:00:00', 'Registro desde terminal técnico.', '2026-07-16 17:50:53', '2026-07-22 16:41:19', NULL),
('019f6c0e-2554-70ed-94ed-4d1ba8283952', 1, 1, 'FINALIZADA', 'Reemplazo completo del interruptor principal del tablero eléctrico.', 'Señor De Exehomo, San Sebastián, Cusco, 08006, Perú', -13.53000000, -71.93000000, NULL, '10:00:00', 'Dejar en recepción', 1, 'Ya tengo el cableado necesario.', '2026-07-16', '10:00:00', 'Registro desde terminal técnico.', '2026-07-16 17:51:34', '2026-07-16 18:04:07', NULL),
('019f6d2a-f251-708d-92bf-c27d9f51cf00', 1, 1, 'FINALIZADA', 'No hay luz en la cocina', 'Patrón San Sebastián, Surihuaylla Grande, San Sebastián, Cusco, 08200, Perú', -13.54153389, -71.93996430, '2026-07-18', '16:00:00', 'Llamar antes de venir', 0, 'Tengo 5 focos pero no sé si servirán', '2026-07-18', '16:00:00', 'Registro desde terminal técnico.', '2026-07-16 23:02:38', '2026-07-17 14:36:05', NULL),
('019f6e0b-56eb-737e-ae10-cf9781a6c4d3', 1, 1, 'FINALIZADA', 'Reemplazo completo del interruptor principal del tablero eléctrico.', 'Av. Sol 123, Cusco', -12.12210000, -77.03050000, '2026-07-17', '12:00:00', 'Solo por las mañanas', 0, 'No tengo materiales.', '2026-07-20', '12:00:00', 'Registro desde terminal técnico.', '2026-07-17 03:07:44', '2026-07-22 17:43:33', NULL),
('019f6e20-8fc3-73ea-98ce-8cf95b195e77', 1, 2, 'ASIGNADA', 'Instalación de un punto eléctrico adicional para escritorio de oficina.', 'Av. Sol 123, Cusco', NULL, NULL, '2026-07-18', '14:00:00', 'Solo por las mañanas', 0, 'Tengo cables para la instalación.', '2026-07-22', '14:00:00', NULL, '2026-07-17 03:30:55', '2026-07-21 14:32:54', NULL),
('019f6e42-efc0-71b9-9d1a-563e7389dc83', 1, 1, 'COTIZADA', 'Cortocircuito en el tablero principal; se requiere atención inmediata.', 'Av. Sol 123, Cusco', -12.12210000, -77.03050000, NULL, '10:00:00', 'Solo por las mañanas', 1, 'No tengo materiales disponibles.', '2026-09-23', '10:00:00', 'Registro desde terminal técnico.', '2026-07-17 04:08:28', '2026-07-22 14:58:31', NULL),
('019f7034-c3a5-73ef-988f-69086a7b34f7', 1, 1, 'FINALIZADA', 'Cambio de luminarias LED en el área del comedor.', 'Señor De Exehomo, San Sebastián, Cusco, 08006, Perú', -13.53000000, -71.93000000, '2026-07-18', '10:00:00', 'Llamar antes de venir', 0, 'Tengo 15 metros de cable THW.', '2026-07-18', '10:00:00', 'Registro desde terminal técnico.', '2026-07-17 13:12:13', '2026-07-17 14:37:02', NULL),
('019f7083-54b5-70e1-a997-03c9ac026cfa', 1, 1, 'COTIZADA', 'El interruptor diferencial se dispara constantemente al conectar electrodomésticos.', 'Paradero Instituto Túpac Amaru, Avenida Cusco, Ayarmaca, Surihuaylla Grande, San Sebastián, Cusco, 08006, Perú', -13.53243950, -71.92844060, NULL, '10:00:00', 'URGENTE - Emergencia Reportada', 1, NULL, '2026-07-23', '15:00:00', 'Registro desde terminal técnico.', '2026-07-17 14:38:02', '2026-07-22 14:47:14', NULL),
('019f7106-9335-72c0-905d-7f4a0ece9154', 1, 2, 'ASIGNADA', 'Diagnóstico de una falla eléctrica en el segundo piso de la vivienda.', 'Av. Sol 123, Cusco', NULL, NULL, NULL, '10:30:00', 'Preferente', 0, NULL, '2026-07-22', '10:30:00', NULL, '2026-07-17 17:01:24', '2026-07-22 14:54:59', NULL),
('019f711d-73b3-70f8-b479-d731e221ea77', 1, 2, 'ASIGNADA', 'Instalación de canaletas y cableado para nuevas tomas eléctricas.', 'Av. Sol 123, Cusco', NULL, NULL, NULL, '10:00:00', 'Preferente', 0, NULL, '2026-07-23', '11:00:00', NULL, '2026-07-17 17:26:23', '2026-07-22 15:48:51', NULL),
('019f78af-d196-7192-b9d3-e1f3386c0913', 1, 1, 'FINALIZADA', 'No hay energía eléctrica en el segundo piso de la casa, parece un cortocircuito en el tablero principal.', 'Av. Los Álamos 123, Urb. San Felipe, Jesús María, Lima', -12.07720000, -77.04280000, '2026-07-25', '10:00:00', 'El portero tiene las llaves. Llamar 10 minutos antes de llegar.', 1, 'El cliente ya compró el breaker y 10 metros de cable, solo falta la mano de obra.', '2026-07-20', '10:00:00', 'Registro desde terminal técnico.', '2026-07-19 04:43:36', '2026-07-22 13:36:26', NULL),
('019f7c61-5df2-7290-9a72-9b5e58440eb4', 1, 1, 'FINALIZADA', 'Revisión del sistema eléctrico por variaciones de voltaje.', 'Paucarbamba, Maras, Urubamba, Cusco, 08660, Perú', -13.28648128, -72.18479603, '2026-07-25', '15:00:00', 'Solo por las mañanas', 0, 'Tengo cables para la instalación.', '2026-07-25', '15:00:00', 'Registro desde terminal técnico.', '2026-07-19 21:56:23', '2026-07-21 17:53:54', NULL),
('019f8509-1e0f-72fa-bf5e-4d9dffad49b0', 1, 1, 'EN_PROCESO', 'Instalación de iluminación exterior para el patio principal.', 'Señor De Exehomo, San Sebastián, Cusco, 08006, Perú', -13.53000000, -71.93000000, '2026-07-25', '11:16:00', 'Dejar en recepción', 0, 'Tengo cable eléctrico.', '2026-07-22', '11:16:00', 'Registro desde terminal técnico.', '2026-07-21 14:16:35', '2026-07-22 13:36:43', NULL),
('019f85c3-be5c-7360-b762-7c92cdd2fb38', 1, 1, 'FINALIZADA', 'Cambio completo del cableado deteriorado de una habitación.', 'Señor De Exehomo, San Sebastián, Cusco, 08006, Perú', -13.53000000, -71.93000000, '2026-07-25', '15:44:00', 'Solo por las mañanas', 0, 'Ya compré el breaker, solo falta instalarlo.', '2026-07-25', '15:44:00', 'Registro desde terminal técnico.', '2026-07-21 17:40:25', '2026-07-22 17:19:44', NULL),
('019f8a16-aaaa-7152-bd8b-30b3a85ce4bb', 1, 1, 'FINALIZADA', 'Instalación de un nuevo interruptor para iluminación del pasillo.', 'Señor De Exehomo, San Sebastián, Cusco, 08006, Perú', -13.53000000, -71.93000000, '2026-07-25', '08:52:00', 'Dejar en recepción', 0, 'Tengo cable eléctrico.', '2026-07-23', '10:30:00', 'Registro desde terminal técnico.', '2026-07-22 13:49:29', '2026-07-22 16:41:59', NULL),
('019f8a64-e3b6-70ba-a944-ad919090b49b', 1, 1, 'FINALIZADA', 'Reparación de un tomacorriente que presenta falso contacto.', 'Señor De Exehomo, San Sebastián, Cusco, 08006, Perú', -13.53000000, -71.93000000, '2026-07-25', '10:17:00', 'Preferente', 0, NULL, '2026-07-25', '10:17:00', 'Registro desde terminal técnico.', '2026-07-22 15:14:55', '2026-07-22 16:41:45', NULL),
('019f8a72-1c8e-72e5-a4ff-742648fb5881', 1, 1, 'FINALIZADA', 'Mantenimiento preventivo del tablero eléctrico residencial.', 'Señor De Exehomo, San Sebastián, Cusco, 08006, Perú', -13.53000000, -71.93000000, '2026-07-25', '10:31:00', 'Preferente', 0, NULL, '2026-07-25', '10:31:00', 'Registro desde terminal técnico.', '2026-07-22 15:29:22', '2026-07-22 15:32:25', NULL),
('019f8a75-c355-70e3-ae6a-51fd2cd29891', 1, 2, 'ASIGNADA', 'Instalación de un punto de energía para equipo de aire acondicionado.', 'Señor De Exehomo, San Sebastián, Cusco, 08006, Perú', -13.53000000, -71.93000000, '2026-07-25', '10:34:00', 'Preferente', 0, NULL, '2026-07-25', '10:34:00', NULL, '2026-07-22 15:33:21', '2026-07-22 15:33:52', NULL),
('019f8a77-6830-70c5-bfc9-b2c59abc1254', 1, 1, 'FINALIZADA', 'Instalación de iluminación exterior para el patio principal.', 'Av. Sol 123, Cusco', NULL, NULL, '2026-07-25', '10:37:00', 'Preferente', 0, NULL, '2026-07-25', '10:37:00', 'Registro desde terminal técnico.', '2026-07-22 15:35:09', '2026-07-22 16:41:32', NULL),
('019f8a81-d9ad-71da-b4d0-453bf061abcf', 1, 1, 'FINALIZADA', 'Cambio de interruptores deteriorados en la sala principal.', 'Señor De Exehomo, San Sebastián, Cusco, 08006, Perú', -13.53000000, -71.93000000, '2026-07-25', '10:48:00', 'Preferente', 0, NULL, '2026-07-25', '10:48:00', 'Registro desde terminal técnico.', '2026-07-22 15:46:33', '2026-07-22 16:41:05', NULL),
('019f8a8c-a04d-7355-9393-a5c2ccc05406', 1, 1, 'FINALIZADA', 'Reparación de una falla eléctrica causada por humedad.', 'Señor De Exehomo, San Sebastián, Cusco, 08006, Perú', -13.53000000, -71.93000000, '2026-07-25', '10:00:00', 'Preferente', 0, NULL, '2026-07-25', '10:30:00', 'Registro desde terminal técnico.', '2026-07-22 15:58:19', '2026-07-22 16:03:44', NULL),
('019f8a94-f17d-736f-8c8e-ea6e6833cd65', 1, 1, 'FINALIZADA', 'Instalación de iluminación exterior para el patio principal.', 'Señor De Exehomo, San Sebastián, Cusco, 08006, Perú', -13.53000000, -71.93000000, '2026-07-25', '11:08:00', 'Preferente', 0, NULL, '2026-07-25', '11:30:00', 'Registro desde terminal técnico.', '2026-07-22 16:07:24', '2026-07-22 16:18:36', NULL),
('019f8a9f-eb47-7132-93d3-44f31997cf4d', 1, 1, 'FINALIZADA', 'Instalación de luminarias LED en el garaje.', 'Av. Sol 123, Cusco', NULL, NULL, '2026-07-24', '11:21:00', 'Preferente', 0, NULL, '2026-07-24', '11:21:00', 'Registro desde terminal técnico.', '2026-07-22 16:19:24', '2026-07-22 16:40:53', NULL),
('019f8aa4-d04e-715a-b720-c33e14ad350d', 1, 1, 'FINALIZADA', 'Revisión del sistema de puesta a tierra de la vivienda.', 'Av. Sol 123, Cusco', NULL, NULL, '2026-07-25', '11:26:00', 'Preferente', 0, NULL, '2026-07-25', '11:30:00', 'Registro desde terminal técnico.', '2026-07-22 16:24:44', '2026-07-22 16:40:41', NULL),
('019f8ab0-dd5d-7272-8186-74d40aa90cd6', 1, 1, 'COTIZADA', 'Elaboración de cotización para renovación completa del sistema eléctrico.', 'Av. Sol 123, Cusco', NULL, NULL, '2026-07-25', '10:37:00', 'Preferente', 0, NULL, '2026-07-26', '10:00:00', 'Registro desde terminal técnico.', '2026-07-22 16:37:54', '2026-07-22 17:41:05', NULL),
('019f8ab3-3cc3-707c-a442-52549bb86c48', 1, 1, 'FINALIZADA', 'Instalación de un extractor con conexión eléctrica en la cocina.', 'Av. Sol 123, Cusco', NULL, NULL, '2026-07-25', '11:43:00', 'Preferente', 0, NULL, '2026-07-25', '11:43:00', 'Registro desde terminal técnico.', '2026-07-22 16:40:30', '2026-07-22 17:06:31', NULL),
('019f8ab3-9ed7-7133-8d0f-2c6803585017', 1, 1, 'FINALIZADA', 'Cambio de cableado eléctrico en dormitorio principal.', 'Av. Sol 123, Cusco', NULL, NULL, '2026-07-25', '11:42:00', 'Preferente', 0, NULL, '2026-07-26', '12:00:00', 'Registro desde terminal técnico.', '2026-07-22 16:40:55', '2026-07-22 16:54:19', NULL),
('019f8ac8-5b1d-73b1-a5f6-1a806b520762', 1, 1, 'FINALIZADA', 'Reparación del sistema de iluminación de la escalera.', 'Av. Sol 123, Cusco', NULL, NULL, '2026-07-25', '12:05:00', 'Preferente', 0, NULL, '2026-07-25', '12:10:00', 'Registro desde terminal técnico.', '2026-07-22 17:03:34', '2026-07-22 17:09:36', NULL),
('019f8ad0-81f0-7128-989b-2b965a3d98ac', 1, 1, 'FINALIZADA', 'Instalación de un circuito independiente para horno eléctrico.', 'Av. Sol 123, Cusco', NULL, NULL, '2026-07-25', '12:14:00', 'Preferente', 0, NULL, '2026-07-27', '13:00:00', 'Registro desde terminal técnico.', '2026-07-22 17:12:28', '2026-07-22 17:34:14', NULL),
('019f8aee-8b36-714c-9868-af4614836584', 1, 1, 'FINALIZADA', 'Revisión general de la instalación eléctrica del inmueble.', 'Av. Sol 123, Cusco', NULL, NULL, NULL, NULL, 'URGENTE - Emergencia Reportada', 1, NULL, '2026-07-25', '11:00:00', 'Registro desde terminal técnico.', '2026-07-22 17:45:16', '2026-07-23 15:09:17', NULL),
('019f8af2-59d7-7004-9828-ec5bd5bfba32', 1, 1, 'FINALIZADA', 'Reparación de un cortocircuito en el circuito de iluminación.', 'Av. Sol 123, Cusco', NULL, NULL, NULL, NULL, 'URGENTE - Emergencia Reportada', 1, NULL, '2026-07-27', '11:00:00', 'Registro desde terminal técnico.', '2026-07-22 17:49:26', '2026-07-23 15:00:50', NULL),
('019f8afd-33c1-70ce-a9c9-ee24f601b048', 1, 1, 'COTIZADA', 'Solicitud de cotización para ampliación de instalaciones eléctricas.', 'Av. Sol 123, Cusco', NULL, NULL, NULL, NULL, 'URGENTE - Emergencia Reportada', 1, NULL, '2026-07-29', '13:00:00', 'Registro desde terminal técnico.', '2026-07-22 18:01:17', '2026-07-22 18:10:38', NULL),
('019f8afd-7f4a-7026-aa37-75c192d4c044', 1, 2, 'COTIZADA', 'Instalación de un tomacorriente industrial para maquinaria.', 'Av. Sol 123, Cusco', NULL, NULL, NULL, NULL, 'URGENTE - Emergencia Reportada', 1, NULL, '2026-07-29', '13:00:00', 'Registro desde terminal técnico.', '2026-07-22 18:01:36', '2026-07-31 14:38:40', NULL),
('019f8b01-7950-7146-91dc-433dc11250ae', 1, 1, 'FINALIZADA', 'Cambio de cableado y canalización en oficina administrativa.', 'Av. Sol 123, Cusco', NULL, NULL, NULL, NULL, 'URGENTE - Emergencia Reportada', 1, NULL, '2026-07-28', '14:00:00', 'Registro desde terminal técnico.', '2026-07-22 18:05:57', '2026-07-23 14:58:41', NULL),
('019f8b01-b8b5-7060-a859-70f8d2ac6042', 1, 1, 'FINALIZADA', 'Reemplazo de luminarias fluorescentes por paneles LED.', 'Av. Sol 123, Cusco', NULL, NULL, NULL, NULL, 'URGENTE - Emergencia Reportada', 1, NULL, '2026-07-26', '17:00:00', 'Registro desde terminal técnico.', '2026-07-22 18:06:13', '2026-07-23 14:58:03', NULL),
('019f8f86-1ce0-7159-b91d-912c430ff512', 1, 1, 'FINALIZADA', 'Instalación de un interruptor inteligente para iluminación.', 'Av. Sol 123, Cusco', NULL, NULL, NULL, NULL, 'URGENTE - Emergencia Reportada', 1, NULL, '2026-07-30', '14:00:00', 'Registro desde terminal técnico.', '2026-07-23 15:09:18', '2026-07-23 15:34:45', NULL),
('019f8f86-63de-714d-9b70-6683256467a9', 1, 1, 'FINALIZADA', 'Reparación de una sobrecarga en el circuito de cocina.', 'Av. Sol 123, Cusco', NULL, NULL, NULL, NULL, 'URGENTE - Emergencia Reportada', 1, NULL, '2026-07-31', '13:00:00', 'Registro desde terminal técnico.', '2026-07-23 15:09:37', '2026-07-23 15:31:58', NULL),
('019f8fae-f51f-734c-85ce-9f231354d96e', 1, 1, 'FINALIZADA', 'instalcion de tomacorrientes', 'Avenida de la Cultura, Barrio Profesional, Cuzco, Distrito de Cusco, Cusco, 08003, Perú', -13.52011639, -71.97140589, '2026-07-25', '14:59:00', 'tocar el timbre', 1, NULL, '2026-07-25', '15:00:00', 'Registro desde terminal técnico.', '2026-07-23 15:53:55', '2026-07-23 16:17:12', NULL),
('019f8faf-cefc-7317-94a7-7aa2708626ad', 4, 2, 'ASIGNADA', 'Revisión de una falla eléctrica general en la vivienda ubicada en Huasao.', 'huasao', NULL, NULL, '2026-07-25', '15:00:00', 'Llamar antes de venir', 0, 'No tengo materiales.', '2026-07-25', '15:00:00', NULL, '2026-07-23 15:54:51', '2026-07-23 16:00:05', NULL),
('019f8fb0-d671-7311-a896-8dffb0622375', 3, 2, 'ASIGNADA', 'Instalación de iluminación exterior para el patio principal.', 'Vía Expresa, Kennedy Sur, Wanchaq, Cusco, 08200, Perú', -13.53424996, -71.94633007, '2026-07-24', '18:30:00', 'Disponible en las tardes, Llamar antes de venir', 0, 'Necesito únicamente la mano de obra.', '2026-07-24', '11:00:00', NULL, '2026-07-23 15:55:58', '2026-07-23 15:59:53', NULL),
('019fb888-962f-7202-84ae-28f2e94b74e2', 1, 2, 'COTIZADA', 'las luces de la cocina no funcionan', 'Av. Sol 123, Cusco', NULL, NULL, '2026-08-08', '14:21:00', 'Preferente', 0, NULL, '2026-08-10', '14:21:00', 'Registro desde terminal técnico.', '2026-07-31 14:16:26', '2026-07-31 14:40:01', NULL),
('019fb8ad-c653-7347-85de-8bebae26f5b3', 1, 1, 'FINALIZADA', 'las luces de la sala parpadean', 'Avenida Cusco, Ayarmaca, Surihuaylla Grande, San Sebastián, Cusco, 08006, Perú', -13.53238550, -71.92745200, '2026-08-01', '13:00:00', 'Preferente', 0, NULL, '2026-08-01', '13:00:00', 'Registro desde terminal técnico.', '2026-07-31 14:57:04', '2026-07-31 15:23:05', NULL),
('019fb8ca-81c7-72a3-9556-e843ae93f217', 1, 1, 'FINALIZADA', 'las luces del patio no prenden', 'Paradero Instituto Túpac Amaru, Avenida Cusco, Ayarmaca, Surihuaylla Grande, San Sebastián, Cusco, 08006, Perú', -13.53304830, -71.92842350, '2026-08-01', NULL, 'URGENTE - Emergencia Reportada', 1, NULL, '2026-08-01', '11:00:00', 'Registro desde terminal técnico.', '2026-07-31 15:28:27', '2026-07-31 15:35:10', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_trabajo`
--

CREATE TABLE `tipos_trabajo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tipos_trabajo`
--

INSERT INTO `tipos_trabajo` (`id`, `nombre`, `descripcion`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Instalación de Tablero Eléctrico', 'Instalación completa de tablero de distribución con breakers', 1, '2026-07-16 17:46:31', '2026-07-16 17:46:31'),
(2, 'Instalación de Tomacorrientes', 'Instalación de puntos eléctricos y tomacorrientes', 1, '2026-07-16 17:46:31', '2026-07-16 17:46:31'),
(3, 'Mantenimiento Preventivo', 'Revisión y mantenimiento preventivo de instalaciones eléctricas', 1, '2026-07-16 17:46:31', '2026-07-16 17:46:31'),
(4, 'Reparación de Cortocircuito', 'Diagnóstico y reparación de fallas eléctricas', 1, '2026-07-16 17:46:31', '2026-07-16 17:46:31'),
(5, 'Instalación de Iluminación LED', 'Instalación de focos y luminarias LED', 1, '2026-07-16 17:46:31', '2026-07-16 17:46:31'),
(6, 'Recableado de Ambiente', 'Reemplazo completo de cableado en un ambiente', 1, '2026-07-16 17:46:31', '2026-07-16 17:46:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_trabajo_items_feedback`
--

CREATE TABLE `tipo_trabajo_items_feedback` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_tipo_trabajo` bigint(20) UNSIGNED NOT NULL,
  `id_item` bigint(20) UNSIGNED NOT NULL,
  `veces_incluido` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_trabajo_items_sugeridos`
--

CREATE TABLE `tipo_trabajo_items_sugeridos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_tipo_trabajo` bigint(20) UNSIGNED NOT NULL,
  `id_item` bigint(20) UNSIGNED NOT NULL,
  `cantidad_sugerida` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unidad_medida` varchar(20) DEFAULT NULL,
  `obligatorio` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tipo_trabajo_items_sugeridos`
--

INSERT INTO `tipo_trabajo_items_sugeridos` (`id`, `id_tipo_trabajo`, `id_item`, `cantidad_sugerida`, `unidad_medida`, `obligatorio`, `created_at`, `updated_at`) VALUES
(1, 1, 5, 1.00, NULL, 1, NULL, NULL),
(2, 1, 3, 2.00, NULL, 1, NULL, NULL),
(3, 1, 4, 2.00, NULL, 1, NULL, NULL),
(4, 1, 1, 10.00, NULL, 1, NULL, NULL),
(5, 1, 13, 2.00, NULL, 1, NULL, NULL),
(6, 1, 15, 1.00, NULL, 1, NULL, NULL),
(7, 2, 7, 1.00, NULL, 1, NULL, NULL),
(8, 2, 8, 1.00, NULL, 1, NULL, NULL),
(9, 2, 1, 5.00, NULL, 1, NULL, NULL),
(10, 2, 11, 2.00, NULL, 1, NULL, NULL),
(11, 2, 13, 1.00, NULL, 1, NULL, NULL),
(12, 2, 19, 1.00, NULL, 1, NULL, NULL),
(13, 3, 13, 1.00, NULL, 1, NULL, NULL),
(14, 3, 16, 1.00, NULL, 1, NULL, NULL),
(15, 4, 3, 1.00, NULL, 1, NULL, NULL),
(16, 4, 1, 10.00, NULL, 1, NULL, NULL),
(17, 4, 13, 1.00, NULL, 1, NULL, NULL),
(18, 4, 17, 1.00, NULL, 1, NULL, NULL),
(19, 4, 18, 1.00, NULL, 1, NULL, NULL),
(20, 5, 9, 4.00, NULL, 1, NULL, NULL),
(21, 5, 10, 4.00, NULL, 1, NULL, NULL),
(22, 5, 8, 2.00, NULL, 1, NULL, NULL),
(23, 5, 12, 5.00, NULL, 1, NULL, NULL),
(24, 5, 11, 2.00, NULL, 1, NULL, NULL),
(25, 5, 19, 1.00, NULL, 1, NULL, NULL),
(26, 6, 1, 50.00, NULL, 1, NULL, NULL),
(27, 6, 2, 20.00, NULL, 1, NULL, NULL),
(28, 6, 12, 10.00, NULL, 1, NULL, NULL),
(29, 6, 11, 5.00, NULL, 1, NULL, NULL),
(30, 6, 13, 3.00, NULL, 1, NULL, NULL),
(31, 6, 20, 1.00, NULL, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `id_rol` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `id_rol`, `email`, `password_hash`, `nombres`, `apellidos`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'admin@sigesto.com', '$2y$12$svNad3N9oVxID0UC6oP3lON6JyvYmS65Rip2RpFkvCBd.c10NDjfS', 'Carlos', 'Administrador', '2026-07-16 17:44:54', '2026-07-16 17:44:54', NULL),
(2, 2, 'tecnico@sigesto.com', '$2y$12$NdAB.oAOfzj2RcSNmDYrO.kFvrH6BJpijni22OdhmmNk1zff8Oh8K', 'Juan', 'Electricista', '2026-07-16 17:44:54', '2026-07-16 17:44:54', NULL),
(3, 3, 'cliente@sigesto.com', '$2y$12$CEe0UtlzoVij9CuSKF7USOmzjorrxwGvngfelhQ0/qpMjJCejjwVm', 'Maria', 'Perez', '2026-07-16 17:44:54', '2026-07-16 17:44:54', NULL),
(4, 2, 'Harry@sigesto.com', '$2y$12$aWvLIZxBwvayRCforegSQOtZgO9i.vXQjKDnVqQbM6E75LDDMrB6.', 'Harry', 'chahuayllo', '2026-07-21 14:30:16', '2026-07-21 14:30:16', NULL),
(5, 3, 'dante@gamil.com', '$2y$12$iWqQ0fiAeENGMri3MCI/4.695SlviGSC99M1TvKDQJPvDzK3KSQtK', 'Dante', 'Quiliche', '2026-07-23 15:52:19', '2026-07-23 15:52:19', NULL),
(6, 3, 'Gabriel@gmail.com', '$2y$12$Q722Hh9C4JIQeNP.nlBO9eb09IieTbS2a2ENE84b2D3TExJDETwqS', 'Gabriel', 'Alvarez', '2026-07-23 15:52:42', '2026-07-23 15:52:42', NULL),
(7, 3, 'gchillihuani3@gmail.com', '$2y$12$nAKYB4EM50WzsmXlNoaRVeHeJGB5RNtvK.Fb/cOpoJ6d7n32LnD0m', 'german', 'chillihuani', '2026-07-23 15:53:02', '2026-07-23 15:53:02', NULL),
(8, 2, 'jhulfoTec@sigesto.com', '$2y$12$DPwG9xc7.ix8YoECa5R02uyuPr7vVD6XiG4Sgdvw/N0BtFrVYWvbm', 'jhulfo', 'alvarez', '2026-07-31 15:19:04', '2026-07-31 15:19:04', NULL),
(9, 2, 'deriantec@sigesto.com', '$2y$12$hf02wxuz.kvgOsMlE4c5eOifOxYV982zd71gbZF.49.0OL565veLa', 'derian', 'zanches', '2026-07-31 15:21:55', '2026-07-31 15:21:55', NULL);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_cotizaciones_resumen`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_cotizaciones_resumen` (
`id_cotizacion` bigint(20) unsigned
,`uuid_solicitud` char(36)
,`estado` enum('BORRADOR','ENVIADA','APROBADA','RECHAZADA','LIQUIDADA')
,`subtotal` decimal(12,2)
,`igv` decimal(12,2)
,`total` decimal(12,2)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_pagos_resumen`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_pagos_resumen` (
`id_pago` bigint(20) unsigned
,`uuid_solicitud` char(36)
,`monto_pagado` decimal(12,2)
,`metodo_pago` enum('EFECTIVO','YAPE','PLIN','TRANSFERENCIA','TARJETA')
,`estado_pago` enum('PENDIENTE_APROBACION','COMPLETADO','RECHAZADO','REEMBOLSADO')
,`fecha_pago` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_solicitudes_resumen`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_solicitudes_resumen` (
`uuid_solicitud` char(36)
,`estado` enum('PENDIENTE','ASIGNADA','COTIZADA','REVISION_PAGO','APROBADA','RECHAZADA','EN_PROCESO','FINALIZADA','PAGADA','CANCELADA')
,`created_at` timestamp
,`id_cliente` bigint(20) unsigned
,`cliente_nombres` varchar(100)
,`cliente_apellidos` varchar(100)
,`id_tecnico` bigint(20) unsigned
,`tecnico_nombres` varchar(100)
,`tecnico_apellidos` varchar(100)
);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD PRIMARY KEY (`id_cotizacion`),
  ADD KEY `idx_estado_cotizacion` (`estado`),
  ADD KEY `idx_solicitud_cotizacion` (`uuid_solicitud`),
  ADD KEY `idx_creador` (`id_usuario_creador`);

--
-- Indices de la tabla `detalle_cotizacion`
--
ALTER TABLE `detalle_cotizacion`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `idx_detalle_cotizacion` (`id_cotizacion`),
  ADD KEY `idx_detalle_item` (`id_item`);

--
-- Indices de la tabla `evidencias`
--
ALTER TABLE `evidencias`
  ADD PRIMARY KEY (`id_evidencia`),
  ADD KEY `idx_evidencia_solicitud` (`uuid_solicitud`);

--
-- Indices de la tabla `historial_estados`
--
ALTER TABLE `historial_estados`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `historial_estados_id_usuario_accion_foreign` (`id_usuario_accion`),
  ADD KEY `idx_historial_solicitud` (`uuid_solicitud`),
  ADD KEY `idx_fecha_cambio` (`fecha_cambio`);

--
-- Indices de la tabla `items_catalogo`
--
ALTER TABLE `items_catalogo`
  ADD PRIMARY KEY (`id_item`),
  ADD UNIQUE KEY `items_catalogo_sku_codigo_unique` (`sku_codigo`),
  ADD KEY `idx_tipo_item` (`tipo_item`),
  ADD KEY `idx_nombre` (`nombre`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `pagos_id_usuario_registro_foreign` (`id_usuario_registro`),
  ADD KEY `pagos_id_usuario_aprobacion_foreign` (`id_usuario_aprobacion`),
  ADD KEY `idx_pago_solicitud` (`uuid_solicitud`),
  ADD KEY `idx_estado_pago` (`estado_pago`),
  ADD KEY `idx_fecha_pago` (`fecha_pago`),
  ADD KEY `idx_tipo_pago` (`tipo_pago`);

--
-- Indices de la tabla `perfiles_admin`
--
ALTER TABLE `perfiles_admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `perfiles_admin_id_usuario_unique` (`id_usuario`);

--
-- Indices de la tabla `perfiles_clientes`
--
ALTER TABLE `perfiles_clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `perfiles_clientes_id_usuario_unique` (`id_usuario`),
  ADD UNIQUE KEY `perfiles_clientes_dni_ruc_unique` (`dni_ruc`);

--
-- Indices de la tabla `perfiles_tecnicos`
--
ALTER TABLE `perfiles_tecnicos`
  ADD PRIMARY KEY (`id_tecnico`),
  ADD UNIQUE KEY `perfiles_tecnicos_id_usuario_unique` (`id_usuario`),
  ADD UNIQUE KEY `perfiles_tecnicos_dni_unique` (`dni`),
  ADD KEY `idx_tecnico_disponible` (`disponible`),
  ADD KEY `idx_tecnico_especialidad` (`especialidad`);

--
-- Indices de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `roles_nombre_unique` (`nombre`);

--
-- Indices de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD PRIMARY KEY (`uuid_solicitud`),
  ADD KEY `idx_estado_solicitud` (`estado`),
  ADD KEY `idx_tecnico` (`id_tecnico`),
  ADD KEY `idx_cliente` (`id_cliente`),
  ADD KEY `idx_fecha_creacion` (`created_at`),
  ADD KEY `idx_estado_tecnico` (`estado`,`id_tecnico`),
  ADD KEY `idx_ubicacion` (`latitud`,`longitud`);

--
-- Indices de la tabla `tipos_trabajo`
--
ALTER TABLE `tipos_trabajo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tipo_trabajo_items_feedback`
--
ALTER TABLE `tipo_trabajo_items_feedback`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tipo_trabajo_items_feedback_id_tipo_trabajo_id_item_unique` (`id_tipo_trabajo`,`id_item`),
  ADD KEY `tipo_trabajo_items_feedback_id_item_foreign` (`id_item`);

--
-- Indices de la tabla `tipo_trabajo_items_sugeridos`
--
ALTER TABLE `tipo_trabajo_items_sugeridos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipo_trabajo_items_sugeridos_id_tipo_trabajo_foreign` (`id_tipo_trabajo`),
  ADD KEY `tipo_trabajo_items_sugeridos_id_item_foreign` (`id_item`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `usuarios_email_unique` (`email`),
  ADD KEY `usuarios_id_rol_foreign` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  MODIFY `id_cotizacion` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de la tabla `detalle_cotizacion`
--
ALTER TABLE `detalle_cotizacion`
  MODIFY `id_detalle` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `evidencias`
--
ALTER TABLE `evidencias`
  MODIFY `id_evidencia` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT de la tabla `historial_estados`
--
ALTER TABLE `historial_estados`
  MODIFY `id_historial` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=289;

--
-- AUTO_INCREMENT de la tabla `items_catalogo`
--
ALTER TABLE `items_catalogo`
  MODIFY `id_item` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `perfiles_admin`
--
ALTER TABLE `perfiles_admin`
  MODIFY `id_admin` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `perfiles_clientes`
--
ALTER TABLE `perfiles_clientes`
  MODIFY `id_cliente` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `perfiles_tecnicos`
--
ALTER TABLE `perfiles_tecnicos`
  MODIFY `id_tecnico` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tipos_trabajo`
--
ALTER TABLE `tipos_trabajo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `tipo_trabajo_items_feedback`
--
ALTER TABLE `tipo_trabajo_items_feedback`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipo_trabajo_items_sugeridos`
--
ALTER TABLE `tipo_trabajo_items_sugeridos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_cotizaciones_resumen`
--
DROP TABLE IF EXISTS `vw_cotizaciones_resumen`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u348616500_sigesto`@`127.0.0.1` SQL SECURITY DEFINER VIEW `vw_cotizaciones_resumen`  AS SELECT `c`.`id_cotizacion` AS `id_cotizacion`, `c`.`uuid_solicitud` AS `uuid_solicitud`, `c`.`estado` AS `estado`, `c`.`subtotal` AS `subtotal`, `c`.`igv` AS `igv`, `c`.`total` AS `total`, `c`.`created_at` AS `created_at` FROM `cotizaciones` AS `c` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_pagos_resumen`
--
DROP TABLE IF EXISTS `vw_pagos_resumen`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u348616500_sigesto`@`127.0.0.1` SQL SECURITY DEFINER VIEW `vw_pagos_resumen`  AS SELECT `p`.`id_pago` AS `id_pago`, `p`.`uuid_solicitud` AS `uuid_solicitud`, `p`.`monto_pagado` AS `monto_pagado`, `p`.`metodo_pago` AS `metodo_pago`, `p`.`estado_pago` AS `estado_pago`, `p`.`fecha_pago` AS `fecha_pago` FROM `pagos` AS `p` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_solicitudes_resumen`
--
DROP TABLE IF EXISTS `vw_solicitudes_resumen`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u348616500_sigesto`@`127.0.0.1` SQL SECURITY DEFINER VIEW `vw_solicitudes_resumen`  AS SELECT `s`.`uuid_solicitud` AS `uuid_solicitud`, `s`.`estado` AS `estado`, `s`.`created_at` AS `created_at`, `c`.`id_cliente` AS `id_cliente`, `ucli`.`nombres` AS `cliente_nombres`, `ucli`.`apellidos` AS `cliente_apellidos`, `t`.`id_tecnico` AS `id_tecnico`, `utec`.`nombres` AS `tecnico_nombres`, `utec`.`apellidos` AS `tecnico_apellidos` FROM ((((`solicitudes` `s` join `perfiles_clientes` `c` on(`c`.`id_cliente` = `s`.`id_cliente`)) join `usuarios` `ucli` on(`ucli`.`id_usuario` = `c`.`id_usuario`)) left join `perfiles_tecnicos` `t` on(`t`.`id_tecnico` = `s`.`id_tecnico`)) left join `usuarios` `utec` on(`utec`.`id_usuario` = `t`.`id_usuario`)) ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD CONSTRAINT `cotizaciones_id_usuario_creador_foreign` FOREIGN KEY (`id_usuario_creador`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `cotizaciones_uuid_solicitud_foreign` FOREIGN KEY (`uuid_solicitud`) REFERENCES `solicitudes` (`uuid_solicitud`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_cotizacion`
--
ALTER TABLE `detalle_cotizacion`
  ADD CONSTRAINT `detalle_cotizacion_id_cotizacion_foreign` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id_cotizacion`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_cotizacion_id_item_foreign` FOREIGN KEY (`id_item`) REFERENCES `items_catalogo` (`id_item`);

--
-- Filtros para la tabla `evidencias`
--
ALTER TABLE `evidencias`
  ADD CONSTRAINT `evidencias_uuid_solicitud_foreign` FOREIGN KEY (`uuid_solicitud`) REFERENCES `solicitudes` (`uuid_solicitud`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_estados`
--
ALTER TABLE `historial_estados`
  ADD CONSTRAINT `historial_estados_id_usuario_accion_foreign` FOREIGN KEY (`id_usuario_accion`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `historial_estados_uuid_solicitud_foreign` FOREIGN KEY (`uuid_solicitud`) REFERENCES `solicitudes` (`uuid_solicitud`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_id_usuario_aprobacion_foreign` FOREIGN KEY (`id_usuario_aprobacion`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `pagos_id_usuario_registro_foreign` FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `pagos_uuid_solicitud_foreign` FOREIGN KEY (`uuid_solicitud`) REFERENCES `solicitudes` (`uuid_solicitud`);

--
-- Filtros para la tabla `perfiles_admin`
--
ALTER TABLE `perfiles_admin`
  ADD CONSTRAINT `perfiles_admin_id_usuario_foreign` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `perfiles_clientes`
--
ALTER TABLE `perfiles_clientes`
  ADD CONSTRAINT `perfiles_clientes_id_usuario_foreign` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `perfiles_tecnicos`
--
ALTER TABLE `perfiles_tecnicos`
  ADD CONSTRAINT `perfiles_tecnicos_id_usuario_foreign` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD CONSTRAINT `solicitudes_id_cliente_foreign` FOREIGN KEY (`id_cliente`) REFERENCES `perfiles_clientes` (`id_cliente`),
  ADD CONSTRAINT `solicitudes_id_tecnico_foreign` FOREIGN KEY (`id_tecnico`) REFERENCES `perfiles_tecnicos` (`id_tecnico`);

--
-- Filtros para la tabla `tipo_trabajo_items_feedback`
--
ALTER TABLE `tipo_trabajo_items_feedback`
  ADD CONSTRAINT `tipo_trabajo_items_feedback_id_item_foreign` FOREIGN KEY (`id_item`) REFERENCES `items_catalogo` (`id_item`) ON DELETE CASCADE,
  ADD CONSTRAINT `tipo_trabajo_items_feedback_id_tipo_trabajo_foreign` FOREIGN KEY (`id_tipo_trabajo`) REFERENCES `tipos_trabajo` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tipo_trabajo_items_sugeridos`
--
ALTER TABLE `tipo_trabajo_items_sugeridos`
  ADD CONSTRAINT `tipo_trabajo_items_sugeridos_id_item_foreign` FOREIGN KEY (`id_item`) REFERENCES `items_catalogo` (`id_item`) ON DELETE CASCADE,
  ADD CONSTRAINT `tipo_trabajo_items_sugeridos_id_tipo_trabajo_foreign` FOREIGN KEY (`id_tipo_trabajo`) REFERENCES `tipos_trabajo` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_id_rol_foreign` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
