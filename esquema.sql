/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.2.2-MariaDB, for Linux (x86_64)
--
-- Host: mysql-metasoft-metasoft.b.aivencloud.com    Database: tecnologico
-- ------------------------------------------------------
-- Server version	8.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `Area`
--

DROP TABLE IF EXISTS `Area`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Area` (
  `idArea` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripccion` text COLLATE utf8mb4_unicode_ci,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idArea`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Aula`
--

DROP TABLE IF EXISTS `Aula`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Aula` (
  `idAula` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombreAula` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idAula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `AulaGrupo`
--

DROP TABLE IF EXISTS `AulaGrupo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `AulaGrupo` (
  `idAula` bigint unsigned NOT NULL,
  `idGrupo` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idAula`,`idGrupo`),
  KEY `aulagrupo_idgrupo_foreign` (`idGrupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Carrera`
--

DROP TABLE IF EXISTS `Carrera`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Carrera` (
  `idCarrera` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombreCarrera` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regimen` enum('Anual','Semestral','Mensual','Otro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Mensual',
  `duracion_meses` int unsigned NOT NULL DEFAULT '0' COMMENT 'Duración total en meses (ej: 36 para 3 años)',
  `cuota_mensual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cuotas_por_anio` tinyint unsigned NOT NULL DEFAULT '12' COMMENT 'Número de cuotas por año (12=anual, 6=semestral, 1=mensual)',
  `duracion` tinyint NOT NULL,
  `cargaHoraria` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `costo` decimal(10,2) NOT NULL,
  `costo_matricula` decimal(10,2) NOT NULL DEFAULT '0.00',
  `denominacionTitutloProfesional` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `estadoCarrera` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `idArea` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idCarrera`),
  KEY `carrera_idarea_foreign` (`idArea`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `CarreraMateria`
--

DROP TABLE IF EXISTS `CarreraMateria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `CarreraMateria` (
  `idCarreraMateria` bigint unsigned NOT NULL AUTO_INCREMENT,
  `idMateria` bigint unsigned NOT NULL,
  `idCarrera` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idCarreraMateria`),
  KEY `carreramateria_idmateria_foreign` (`idMateria`),
  KEY `carreramateria_idcarrera_foreign` (`idCarrera`)
) ENGINE=InnoDB AUTO_INCREMENT=193 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `CarreraUsuario`
--

DROP TABLE IF EXISTS `CarreraUsuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `CarreraUsuario` (
  `idCarreraUsuario` bigint unsigned NOT NULL AUTO_INCREMENT,
  `idCarrera` bigint unsigned NOT NULL,
  `idUsuario` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idCarreraUsuario`),
  KEY `carrerausuario_idcarrera_foreign` (`idCarrera`),
  KEY `carrerausuario_idusuario_foreign` (`idUsuario`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Cuota`
--

DROP TABLE IF EXISTS `Cuota`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Cuota` (
  `idCuota` bigint unsigned NOT NULL AUTO_INCREMENT,
  `monto` decimal(10,2) NOT NULL,
  `numeroCuota` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('MATRICULA','MENSUAL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MENSUAL',
  `descuento` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fecha_vencimiento` date DEFAULT NULL,
  `estadoCuota` enum('Debe','Pagado','Condonado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Debe',
  `fecha_pago` datetime DEFAULT NULL,
  `idUsuario` bigint unsigned NOT NULL,
  `idCarrera` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idCuota`),
  KEY `cuota_idusuario_foreign` (`idUsuario`),
  KEY `cuota_idcarrera_foreign` (`idCarrera`)
) ENGINE=InnoDB AUTO_INCREMENT=365 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Docente`
--

DROP TABLE IF EXISTS `Docente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Docente` (
  `idDocente` bigint unsigned NOT NULL,
  `profesion` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abreviaturaProfesional` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fechaRegistro` date NOT NULL,
  `estadoDocente` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`idDocente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DocumentoEstudiante`
--

DROP TABLE IF EXISTS `DocumentoEstudiante`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `DocumentoEstudiante` (
  `idDocumentoEstudiante` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombreDocumento` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ubicacionArchivo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `estadoDocumento` enum('Debe','Entregado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Debe',
  `idUsuario` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idDocumentoEstudiante`),
  KEY `documentoestudiante_idusuario_foreign` (`idUsuario`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ElementoCompetencia`
--

DROP TABLE IF EXISTS `ElementoCompetencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ElementoCompetencia` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_grupo_materia_docente` bigint unsigned NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `elementocompetencia_id_grupo_materia_docente_foreign` (`id_grupo_materia_docente`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Estudiante`
--

DROP TABLE IF EXISTS `Estudiante`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Estudiante` (
  `id_usuario` bigint unsigned NOT NULL,
  `matricula` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `estudiante_matricula_unique` (`matricula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FechaRazon`
--

DROP TABLE IF EXISTS `FechaRazon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `FechaRazon` (
  `idFechaRazon` bigint unsigned NOT NULL AUTO_INCREMENT,
  `razon` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idFechaRazon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Gestion`
--

DROP TABLE IF EXISTS `Gestion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Gestion` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `anio` year NOT NULL,
  `periodo` enum('Anual','Semestral','Trimestral','Mensual','Otro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Anual',
  `estado` enum('activo','inactivo','cerrado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Grupo`
--

DROP TABLE IF EXISTS `Grupo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Grupo` (
  `idGrupo` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `paralelo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `turno` enum('Mañana','Tarde','Noche') COLLATE utf8mb4_unicode_ci NOT NULL,
  `gestion` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cupos` int NOT NULL,
  `tipo` enum('Capacitacion','Curso') COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idGrupo`)
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `GrupoHorario`
--

DROP TABLE IF EXISTS `GrupoHorario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `GrupoHorario` (
  `idGrupoHorario` bigint unsigned NOT NULL AUTO_INCREMENT,
  `idGrupo` bigint unsigned NOT NULL,
  `idHorario` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idGrupoHorario`),
  UNIQUE KEY `grupohorario_idgrupo_idhorario_unique` (`idGrupo`,`idHorario`),
  KEY `grupohorario_idhorario_foreign` (`idHorario`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `GrupoMateriaDocente`
--

DROP TABLE IF EXISTS `GrupoMateriaDocente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `GrupoMateriaDocente` (
  `idGrupoMateriaDocente` bigint unsigned NOT NULL AUTO_INCREMENT,
  `idGrupo` bigint unsigned NOT NULL,
  `idMateria` bigint unsigned NOT NULL,
  `idDocente` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idGrupoMateriaDocente`),
  KEY `grupomateriadocente_idgrupo_foreign` (`idGrupo`),
  KEY `grupomateriadocente_idmateria_foreign` (`idMateria`),
  KEY `grupomateriadocente_iddocente_foreign` (`idDocente`)
) ENGINE=InnoDB AUTO_INCREMENT=358 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Horario`
--

DROP TABLE IF EXISTS `Horario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Horario` (
  `idHorario` bigint unsigned NOT NULL AUTO_INCREMENT,
  `horaInicio` time NOT NULL,
  `horaFin` time NOT NULL,
  `dia` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idHorario`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Inscripcion`
--

DROP TABLE IF EXISTS `Inscripcion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Inscripcion` (
  `idInscripcion` bigint unsigned NOT NULL AUTO_INCREMENT,
  `idGrupo` bigint unsigned NOT NULL,
  `idUsuario` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idInscripcion`),
  KEY `inscripcion_idgrupo_foreign` (`idGrupo`),
  KEY `inscripcion_idusuario_foreign` (`idUsuario`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ListaAsistencia`
--

DROP TABLE IF EXISTS `ListaAsistencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ListaAsistencia` (
  `idListaAsistencia` bigint unsigned NOT NULL AUTO_INCREMENT,
  `observacion` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `id_grupo_materia_docente` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idListaAsistencia`),
  KEY `la_grupo_materia_docente_foreign` (`id_grupo_materia_docente`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ListaAsistenciaInscripcion`
--

DROP TABLE IF EXISTS `ListaAsistenciaInscripcion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ListaAsistenciaInscripcion` (
  `idListaAsistenciaInscripcion` bigint unsigned NOT NULL AUTO_INCREMENT,
  `observacion` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('Presente','Permiso','Falta','Atraso') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` date NOT NULL,
  `idHorario` bigint unsigned DEFAULT NULL,
  `idInscripcion` bigint unsigned NOT NULL,
  `idListaAsistencia` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idListaAsistenciaInscripcion`),
  UNIQUE KEY `unique_asistencia_diaria_horario` (`idInscripcion`,`idListaAsistencia`,`fecha`,`idHorario`),
  KEY `listaasistenciainscripcion_idlistaasistencia_foreign` (`idListaAsistencia`),
  KEY `listaasistenciainscripcion_idhorario_foreign` (`idHorario`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Materia`
--

DROP TABLE IF EXISTS `Materia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Materia` (
  `idMateria` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombreMateria` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semestre` tinyint NOT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `idPrerequisito` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idMateria`),
  KEY `materia_idprerequisito_foreign` (`idPrerequisito`)
) ENGINE=InnoDB AUTO_INCREMENT=180 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `NotaElementoCompetencia`
--

DROP TABLE IF EXISTS `NotaElementoCompetencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `NotaElementoCompetencia` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_elemento_competencia` bigint unsigned NOT NULL,
  `id_inscripcion` bigint unsigned NOT NULL,
  `puntaje` decimal(5,2) NOT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nota_ec_unique` (`id_elemento_competencia`,`id_inscripcion`),
  KEY `notaelementocompetencia_id_inscripcion_foreign` (`id_inscripcion`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `NotaFinal`
--

DROP TABLE IF EXISTS `NotaFinal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `NotaFinal` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_inscripcion` bigint unsigned NOT NULL,
  `id_grupo_materia_docente` bigint unsigned NOT NULL,
  `nota_asistencia` decimal(5,2) NOT NULL,
  `nota_academica` decimal(5,2) NOT NULL,
  `nota_final` decimal(5,2) NOT NULL,
  `estado` enum('Aprobado','Reprobado','Abandono') COLLATE utf8mb4_unicode_ci NOT NULL,
  `segunda_instancia_nota` decimal(5,2) DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `calificado_por` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nota_final_unique` (`id_inscripcion`,`id_grupo_materia_docente`),
  KEY `notafinal_id_grupo_materia_docente_foreign` (`id_grupo_materia_docente`),
  KEY `notafinal_calificado_por_foreign` (`calificado_por`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `NumeroReferencia`
--

DROP TABLE IF EXISTS `NumeroReferencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `NumeroReferencia` (
  `idNumeroReferencia` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parentesco` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numeroReferencia` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombreContactoReferencia` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `idUsuario` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idNumeroReferencia`),
  KEY `numeroreferencia_idusuario_foreign` (`idUsuario`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Observacion`
--

DROP TABLE IF EXISTS `Observacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Observacion` (
  `idObservacion` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombreObservacion` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `idUsuario` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idObservacion`),
  KEY `observacion_idusuario_foreign` (`idUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Pago`
--

DROP TABLE IF EXISTS `Pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Pago` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `idUsuario` bigint unsigned NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo` enum('EFECTIVO','TRANSFERENCIA','TARJETA','QR') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EFECTIVO',
  `comprobante` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacion` text COLLATE utf8mb4_unicode_ci,
  `registrado_por` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pago_idusuario_foreign` (`idUsuario`),
  KEY `pago_registrado_por_foreign` (`registrado_por`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `empresa`
--

DROP TABLE IF EXISTS `empresa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresa` (
  `ID_EMPRESA` int NOT NULL AUTO_INCREMENT,
  `EMPRESA` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `SLOGAN` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `SIGLA` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `TELEFONO` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `CELULAR` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `EMAIL` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `DIRECCION` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `RESPONSABLE` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `LATITUD` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `LONGITUD` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `OBJETO` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `MISION` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `VISION` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `FACEBOOK` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `INSTAGRAM` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `TIKTOK` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `LINKEDIN` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `CARRITO` enum('ACTIVO','INACTIVO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVO',
  `TIPO_CAMBIO` decimal(10,2) NOT NULL,
  `LOGO_CUADRADO` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `LOGO_LARGO` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `BANER_INICIO` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ICONO` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `TITULO_CIERRE` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `MENSAJE_CIERRE` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `TITULO_INICIO` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `MENSAJE_INICIO` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `DOMINIO` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `SMTP_CORREO` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `CORREO_INSTITUCIONAL` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `PWD_INSTITUCIONAL` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`ID_EMPRESA`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `formulario`
--

DROP TABLE IF EXISTS `formulario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `formulario` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `formulario` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `ruta` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `formulario_modulo`
--

DROP TABLE IF EXISTS `formulario_modulo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `formulario_modulo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_modulo` bigint unsigned NOT NULL,
  `id_formulario` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `formulario_modulo_id_modulo_id_formulario_unique` (`id_modulo`,`id_formulario`),
  KEY `formulario_modulo_id_formulario_foreign` (`id_formulario`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `formulario_permiso`
--

DROP TABLE IF EXISTS `formulario_permiso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `formulario_permiso` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_rol` bigint unsigned NOT NULL,
  `id_modulo` bigint unsigned NOT NULL,
  `id_formulario` bigint unsigned NOT NULL,
  `puede_crear` tinyint NOT NULL DEFAULT '0',
  `puede_leer` tinyint NOT NULL DEFAULT '0',
  `puede_editar` tinyint NOT NULL DEFAULT '0',
  `puede_eliminar` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `formulario_permiso_id_rol_id_modulo_id_formulario_unique` (`id_rol`,`id_modulo`,`id_formulario`),
  KEY `formulario_permiso_id_modulo_foreign` (`id_modulo`),
  KEY `formulario_permiso_id_formulario_foreign` (`id_formulario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modulo`
--

DROP TABLE IF EXISTS `modulo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `modulo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `modulo` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `icono` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modulo_rol`
--

DROP TABLE IF EXISTS `modulo_rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `modulo_rol` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_rol` bigint unsigned NOT NULL,
  `id_modulo` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modulo_rol_id_rol_id_modulo_unique` (`id_rol`,`id_modulo`),
  KEY `modulo_rol_id_modulo_foreign` (`id_modulo`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `observaciones_usuario`
--

DROP TABLE IF EXISTS `observaciones_usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `observaciones_usuario` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `tipo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `creado_por` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `observaciones_usuario_user_id_foreign` (`user_id`),
  KEY `observaciones_usuario_creado_por_foreign` (`creado_por`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pago_cuota`
--

DROP TABLE IF EXISTS `pago_cuota`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pago_cuota` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `idPago` bigint unsigned NOT NULL,
  `idCuota` bigint unsigned NOT NULL,
  `monto_pagado` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pago_cuota_idpago_foreign` (`idPago`),
  KEY `pago_cuota_idcuota_foreign` (`idCuota`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_codes`
--

DROP TABLE IF EXISTS `password_reset_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `correo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `password_reset_codes_correo_index` (`correo`(250)),
  KEY `password_reset_codes_code_index` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `tokenable_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `registro_accesos`
--

DROP TABLE IF EXISTS `registro_accesos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `registro_accesos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `tipo_persona` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_mostrado` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_alerta` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `punto_control` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `registro_accesos_user_id_foreign` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rol`
--

DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rol` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rol_rol_unique` (`rol`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sucursal`
--

DROP TABLE IF EXISTS `sucursal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sucursal` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sucursal` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `empresa` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsable` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `longitud` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitud` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `celular` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pais` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ciudad` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `localidad` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVO',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usuario` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ci` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombres` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidoPaterno` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apellidoMaterno` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `genero` enum('MASCULINO','FEMENINO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_nac` date NOT NULL,
  `email` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `celular` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expedido` enum('LPZ','CBBA','OR','PT','TJ','SCZ','BN','PD','CH','QR','EXT') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_qr` text COLLATE utf8mb4_unicode_ci,
  `verificacion` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVO',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_usuario_unique` (`usuario`),
  UNIQUE KEY `user_ci_unique` (`ci`)
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_rol`
--

DROP TABLE IF EXISTS `user_rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_rol` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_user` bigint unsigned NOT NULL,
  `id_rol` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_rol_id_user_id_rol_unique` (`id_user`,`id_rol`),
  KEY `user_rol_id_rol_foreign` (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_sucursal`
--

DROP TABLE IF EXISTS `user_sucursal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sucursal` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_user` bigint unsigned NOT NULL,
  `id_sucursal` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_sucursal_id_user_id_sucursal_unique` (`id_user`,`id_sucursal`),
  KEY `user_sucursal_id_sucursal_foreign` (`id_sucursal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-06-29 15:28:32
