<?php
/**
 * AND PROJECTS APP - API para obtener tareas predecesoras
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/paths.php';
require_once __DIR__ . '/../../../utils/session.php';
requireUserAuth();

require_once __DIR__ . '/../models/TareaModel.php';

$proyectoId = isset($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : 0;
$excludeTareaId = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

if (!$proyectoId) {
    echo json_encode([]);
    exit;
}

$model = new TareaModel();
$tareas = $model->getTareasPredecesoras($proyectoId, $excludeTareaId);

echo json_encode($tareas);

