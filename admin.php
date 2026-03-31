<?php
session_start();

$password = 'mudar123'; // Senha de acesso
$dataFile = 'data.json';

// Controle de Sessão
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Autenticação de Usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    if ($_POST['password'] === $password) {
        $_SESSION['logged_in'] = true;
    } else {
        $error = "Senha incorreta.";
    }
}

// Inicialização de Dados via JSON
if (!file_exists($dataFile)) {
    $data = [
        "hero_title_1" => "", "hero_title_destaque" => "", "hero_title_2" => "", "hero_subtitle" => "", 
        "sobre_bio_1" => "", "sobre_bio_2" => "", "sobre_bio_3" => "", 
        "depoimentos" => [], "faq" => [],
        "whatsapp_number" => "5531994945801",
        "stat_1_num" => "+10", "stat_1_text" => "Anos de experiência clínica",
        "stat_2_num" => "+16", "stat_2_text" => "Formações Nacionais e Internacionais",
        "stat_3_num" => "100%", "stat_3_text" => "Atendimento individual e personalizado",
        "insta_url" => "https://www.instagram.com/p/DB1eN8fR9U0/"
    ];
} else {
    $data = json_decode(file_get_contents($dataFile), true);
}

// Interceptação e Processamento de Formulário de Edição
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    
    // Tratamento de Dados Textuais
    $data['hero_title_1'] = $_POST['hero_title_1'];
    $data['hero_title_destaque'] = $_POST['hero_title_destaque'];
    $data['hero_title_2'] = $_POST['hero_title_2'];
    $data['hero_subtitle'] = $_POST['hero_subtitle'];
    $data['sobre_bio_1'] = $_POST['sobre_bio_1'];
    $data['sobre_bio_2'] = $_POST['sobre_bio_2'];
    $data['sobre_bio_3'] = $_POST['sobre_bio_3'];

    // Dados Globais
    $data['whatsapp_number'] = $_POST['whatsapp_number'] ?? ($data['whatsapp_number'] ?? "5531994945801");
    $data['stat_1_num'] = $_POST['stat_1_num'] ?? ($data['stat_1_num'] ?? "+10");
    $data['stat_1_text'] = $_POST['stat_1_text'] ?? ($data['stat_1_text'] ?? "Anos de experiência clínica");
    $data['stat_2_num'] = $_POST['stat_2_num'] ?? ($data['stat_2_num'] ?? "+16");
    $data['stat_2_text'] = $_POST['stat_2_text'] ?? ($data['stat_2_text'] ?? "Formações Nacionais e Internacionais");
    $data['stat_3_num'] = $_POST['stat_3_num'] ?? ($data['stat_3_num'] ?? "100%");
    $data['stat_3_text'] = $_POST['stat_3_text'] ?? ($data['stat_3_text'] ?? "Atendimento individual e personalizado");
    
    // Tratamento Array Instagram
    $insta_urls = [];
    if (isset($_POST['insta_urls']) && is_array($_POST['insta_urls'])) {
        foreach ($_POST['insta_urls'] as $url) {
            if (trim($url) !== '') $insta_urls[] = trim($url);
        }
    }
    // Preserva fallbacks caso o usuario não tenha submetido o form recém alterado (opcional, mas garante null-safe)
    if (empty($insta_urls) && isset($_POST['insta_url'])) {
        $insta_urls = [trim($_POST['insta_url'])];
    }
    $data['insta_urls'] = $insta_urls;

    // Tratamento e Upload de Mídia (Imagens) via WebP
    $uploadDir = 'Images Banner/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    function handleUpload($fileInputName, $currentPath, $uploadDir) {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES[$fileInputName]['tmp_name'];
            $name = basename($_FILES[$fileInputName]['name']);
            $name = preg_replace("/[^A-Za-z0-9\.\-]/", '_', $name); // Sanitize
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $targetName = uniqid() . '_' . pathinfo($name, PATHINFO_FILENAME) . '.webp';
            $targetPath = $uploadDir . $targetName;

            // Otimizador WebP On-the-fly
            $isProcessed = false;
            // Verifica se a função existe para nao quebrar em servidores limitados
            if (function_exists('imagewebp') && in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                if ($extension === 'png') {
                    $image = @imagecreatefrompng($tmpName);
                    if ($image) { imagepalettetotruecolor($image); imagealphablending($image, true); imagesavealpha($image, true); }
                } elseif ($extension === 'webp') {
                    $image = @imagecreatefromwebp($tmpName);
                } else {
                    $image = @imagecreatefromjpeg($tmpName);
                }

                if (isset($image) && $image !== false) {
                    imagewebp($image, $targetPath, 85); // Compressão com 85% de qualidade
                    imagedestroy($image);
                    $isProcessed = true;
                    return $targetPath;
                }
            }
            
            // Fallback se n for imagem suportada pelo GD
            if (!$isProcessed) {
                $fallbackPath = $uploadDir . uniqid() . '_' . $name;
                if (move_uploaded_file($tmpName, $fallbackPath)) {
                    return $fallbackPath;
                }
            }
        }
        return $currentPath ?? '';
    }

    $data['hero_bg_path'] = handleUpload('hero_bg', $data['hero_bg_path'] ?? null, $uploadDir);
    $data['sobre_foto_path'] = handleUpload('sobre_foto', $data['sobre_foto_path'] ?? null, $uploadDir);
    
    // Construção Dinâmica da Array de Testemunhos
    $depoimentos = [];
    if (isset($_POST['dep_autor']) && is_array($_POST['dep_autor'])) {
        for ($i = 0; $i < count($_POST['dep_autor']); $i++) {
            $depoimentos[] = [
                'id' => uniqid(),
                'autor' => $_POST['dep_autor'][$i],
                'texto' => $_POST['dep_texto'][$i],
                'estrelas' => (int)$_POST['dep_estrelas'][$i]
            ];
        }
    }
    $data['depoimentos'] = $depoimentos;

    // Construção Dinâmica da Array de FAQ
    $faq = [];
    if (isset($_POST['faq_pergunta']) && is_array($_POST['faq_pergunta'])) {
        for ($i = 0; $i < count($_POST['faq_pergunta']); $i++) {
            $faq[] = [
                'id' => uniqid(),
                'pergunta' => $_POST['faq_pergunta'][$i],
                'resposta' => $_POST['faq_resposta'][$i]
            ];
        }
    }
    $data['faq'] = $faq;
    
    // Escrita e Validação no Arquivo de Banco de Dados
    $jsonString = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($dataFile, $jsonString) !== false) {
        $success = "Alterações salvas com sucesso!";
    } else {
        $errorSave = "Erro ao salvar arquivo. Verifique permissões.";
    }
}

// Roteamento da Interface Visual (Login vs Dashboard)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login - Dra. Fernanda Oliveira</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Jost', sans-serif; background: #061C36; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: rgba(15, 53, 81, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); padding: 50px 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); text-align: center; border: 1px solid rgba(94, 190, 189, 0.2); max-width: 400px; width: 100%;}
        .login-logo { max-width: 160px; margin-bottom: 25px; filter: drop-shadow(0px 4px 6px rgba(0,0,0,0.5));}
        input { width: 100%; padding: 14px; margin: 15px 0; border-radius: 6px; border: 1px solid #2E7588; background: rgba(0,0,0,0.4); color: #fff; display: block; box-sizing: border-box; font-family: 'Jost', sans-serif; transition: 0.3s; font-size: 16px;}
        input:focus { border-color: #5EBEBD; outline: none; background: rgba(0,0,0,0.6);}
        button { background: #5EBEBD; color: #061C36; border: none; padding: 14px 20px; font-weight: 600; border-radius: 6px; cursor: pointer; width: 100%; display: block; box-sizing: border-box; font-size: 16px; transition: 0.3s;}
        button:hover { background: #4499A3; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(94, 190, 189, 0.2); }
    </style>
</head>
<body>
    <div class="login-box">
        <img src="Images Banner/LOGO.png" alt="Logo Fernanda Oliveira" class="login-logo">
        <p style="color: #aaa; margin-bottom: 25px;">Acesse seu painel administrativo</p>
        <?php if(isset($error)) echo "<p style='color:#ff6b6b; font-weight:500;'>$error</p>"; ?>
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <input type="password" name="password" placeholder="Sua senha de acesso" required>
            <button type="submit">Entrar no Painel</button>
        </form>
    </div>
</body>
</html>
<?php
exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Fernanda Oliveira</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --dark: #061C36; --darkLighter: #0F3551; --accent: #5EBEBD; --accentHover: #4499A3; --textMain: #e0e0e0; --bgElement: #1D536C; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Jost', sans-serif; background: var(--darkLighter); color: var(--textMain); padding-bottom: 100px;}
        /* Estilos Globais e Variáveis de Tema */
        :root { --dark: #061C36; --darkLighter: #0F3551; --accent: #5EBEBD; --accentHover: #4499A3; --textMain: #e0e0e0; --bgElement: #1D536C; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Jost', sans-serif; background: var(--darkLighter); color: var(--textMain); padding-bottom: 100px;}
        
        /* Componente Header */
        .header { background: var(--dark); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(94, 190, 189, 0.2); position: sticky; top: 0; z-index: 10; box-shadow: 0 4px 20px rgba(0,0,0,0.5);}
        .header-brand { display: flex; align-items: center; gap: 15px;}
        .header-logo { height: 45px; filter: brightness(0) invert(1); }
        .header-title { font-family: 'Cormorant Garamond', serif; font-size: 24px; color: var(--accent); margin: 0; font-weight: 500;}
        .logout-btn { color: var(--accent); text-decoration: none; font-weight: 500; border: 1px solid var(--accent); padding: 8px 18px; border-radius: 6px; transition: 0.3s;}
        .logout-btn:hover { background: var(--accent); color: var(--dark);}
        
        /* Sistema de Navegação Interno (Abas) */
        .container { max-width: 900px; margin: 40px auto; }
        .tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 1px solid #1D536C; padding-bottom: 10px; overflow-x: auto; }
        .tab-btn { background: transparent; color: #81abbc; border: none; font-family: 'Jost', sans-serif; font-size: 16px; font-weight: 500; padding: 10px 20px; cursor: pointer; border-radius: 6px; transition: 0.3s; white-space: nowrap;}
        .tab-btn:hover { color: #fff; background: rgba(255,255,255,0.05); }
        .tab-btn.active { color: var(--accent); background: rgba(94, 190, 189, 0.1); }
        
        /* Painéis de Conteúdo */
        .tab-content { display: none; background: var(--dark); border: 1px solid #1D536C; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); }
        .tab-content.active { display: block; animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* UI Base para Formulários */
        h2 { font-family: 'Cormorant Garamond', serif; font-size: 28px; color: #fff; margin-top: 0; border-bottom: 1px solid #1D536C; padding-bottom: 15px; margin-bottom: 30px;}
        .form-group { margin-bottom: 25px; }
        label { display: block; font-weight: 500; color: #fff; margin-bottom: 8px; font-size: 15px; }
        .helper-text { font-size: 13px; color: #a3c7d6; margin-bottom: 10px; display: block; }
        
        input[type="text"], textarea, input[type="number"] { width: 100%; padding: 14px; border-radius: 6px; border: 1px solid #2E7588; background: var(--bgElement); color: #fff; font-family: 'Jost', sans-serif; font-size: 16px; transition: 0.3s; }
        textarea { min-height: 120px; line-height: 1.5; resize: vertical; }
        input:focus, textarea:focus { border-color: var(--accent); outline: none; box-shadow: 0 0 0 2px rgba(94, 190, 189, 0.2); }
        
        /* Estilização Customizada de Inserção de Arquivos (Inputs) */
        .file-upload-wrapper { display: flex; align-items: center; gap: 20px; background: rgba(255,255,255,0.03); padding: 20px; border-radius: 8px; border: 1px dashed #2E7588; }
        .img-preview { width: 120px; height: 80px; object-fit: cover; border-radius: 6px; border: 2px solid #2E7588; box-shadow: 0 4px 10px rgba(0,0,0,0.5);}
        .file-input { display: none; }
        .file-label { display: inline-block; background: #2E7588; color: #fff; padding: 10px 20px; border-radius: 6px; cursor: pointer; transition: 0.3s; font-size: 14px; border: 1px solid #2E7588;}
        .file-label:hover { background: var(--accentHover); border-color: var(--accent); }
        .file-name { font-size: 13px; color: #a3c7d6; margin-top: 8px; font-style: italic;}

        /* Estrutura de Repeater para Depoimentos */
        .depoimento-row { background: var(--bgElement); padding: 25px; border: 1px solid #2E7588; margin-bottom: 25px; border-radius: 10px; position: relative; box-shadow: inset 0 2px 4px rgba(0,0,0,0.2); }
        .remove-btn { background: rgba(217, 56, 56, 0.1); color: #ff6b6b; border: 1px solid rgba(217, 56, 56, 0.3); padding: 8px 15px; border-radius: 6px; cursor: pointer; font-size: 14px; transition: 0.3s; display: inline-flex; align-items: center; gap: 6px;}
        .remove-btn:hover { background: #d93838; color: white; border-color: #d93838; }
        .btn-add { background: transparent; color: var(--accent); border: 2px dashed var(--accent); width: 100%; padding: 18px; font-size: 16px; cursor: pointer; border-radius: 8px; transition: 0.3s; font-weight: 500;}
        .btn-add:hover { background: rgba(94, 190, 189, 0.05); }

        /* Barra Inferior Fixa (Ações Principais) */
        .save-bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(6, 28, 54, 0.95); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border-top: 1px solid rgba(94, 190, 189, 0.3); padding: 20px; display: flex; justify-content: center; gap: 20px; z-index: 100; box-shadow: 0 -10px 30px rgba(0,0,0,0.6);}
        .btn-primary { background: var(--accent); color: var(--dark); border: none; padding: 14px 30px; font-weight: 600; font-family: 'Jost', sans-serif; border-radius: 6px; cursor: pointer; font-size: 16px; box-shadow: 0 4px 15px rgba(94, 190, 189, 0.3); transition: 0.3s;}
        .btn-primary:hover { background: var(--accentHover); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(94, 190, 189, 0.4);}
        .btn-secondary { background: transparent; color: #fff; border: 1px solid #2E7588; padding: 14px 30px; font-weight: 500; font-family: 'Jost', sans-serif; border-radius: 6px; cursor: pointer; font-size: 16px; text-decoration: none;}
        .btn-secondary:hover { background: rgba(255,255,255,0.05); border-color: #fff; }

        /* Sistema de Alerta via Toast Animado */
        .toast { position: fixed; bottom: 100px; left: 50%; transform: translateX(-50%) translateY(20px); opacity: 0; background: #234f2b; color: #b1e8ba; padding: 16px 24px; border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.5); font-weight: 500; display: flex; align-items: center; gap: 12px; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); z-index: 1000; border: 1px solid #357a41; pointer-events: none;}
        .toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
        .toast-error { background: #4f2323; color: #e8b1b1; border-color: #7a3535; }

        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 15px; padding: 20px;}
            .logout-btn { position: absolute; top: 20px; right: 20px; padding: 6px 12px; font-size: 14px;}
            .file-upload-wrapper { flex-direction: column; align-items: flex-start; }
            .save-bar { flex-direction: column; padding: 15px;}
            .btn-primary, .btn-secondary { width: 100%; text-align: center;}
            .container { margin: 20px; }
            .tab-btn { padding: 10px 15px;}
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-brand">
        <img src="Images Banner/LOGO.png" alt="Logo" class="header-logo">
        <h1 class="header-title">Painel de Edição</h1>
    </div>
    <a href="?logout=1" class="logout-btn">Desconectar</a>
</div>

<?php 
// Passagem de Parâmetros de Retorno do Servidor para o Front-End
$toastMsg = ""; $toastType = "";
if(isset($success)) { $toastMsg = $success; $toastType = "success"; }
if(isset($errorSave)) { $toastMsg = $errorSave; $toastType = "error"; }
?>

<!-- Componente: Toast de Alerta Flutuante -->
<div id="toast" class="toast">
    <span class="icon">✅</span>
    <span id="toast-text">Salvo!</span>
</div>

<div class="container">
    <div class="tabs">
        <button type="button" class="tab-btn active" onclick="openTab(event, 'tab-inicio')">Início (Capa)</button>
        <button type="button" class="tab-btn" onclick="openTab(event, 'tab-sobre')">Sobre Mim</button>
        <button type="button" class="tab-btn" onclick="openTab(event, 'tab-depoimentos')">Avaliações</button>
        <button type="button" class="tab-btn" onclick="openTab(event, 'tab-faq')">Perguntas (FAQ)</button>
        <button type="button" class="tab-btn" onclick="openTab(event, 'tab-geral')">Configurações Gerais</button>
    </div>
    
    <form method="POST" id="adminForm" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        
        <!-- Tab 1: Início -->
        <div id="tab-inicio" class="tab-content active">
            <h2>Seção Início (Destaque Principal)</h2>
            <p style="color:#aaa; margin-bottom: 30px;">Essa é a primeira coisa que os clientes verão ao abrir o site.</p>
            
            <div class="form-group">
                <label>Foto de Fundo (Atrás de Você)</label>
                <div class="file-upload-wrapper">
                    <?php if(!empty($data['hero_bg_path'])): ?>
                        <img src="<?php echo htmlspecialchars($data['hero_bg_path']); ?>" alt="Fundo Atual" class="img-preview">
                    <?php endif; ?>
                    <div>
                        <label class="file-label">
                            <input type="file" name="hero_bg" accept="image/*" class="file-input" onchange="updateFileName(this)">
                            Pressione para trocar Imagem...
                        </label>
                        <div class="file-name">Nenhuma nova enviada (A atual está mantida).</div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Início do Título (Antes do Destaque)</label>
                <input type="text" name="hero_title_1" value="<?php echo htmlspecialchars($data['hero_title_1'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Termo Central (Este texto ficará com a cor destaque - Teal)</label>
                <input type="text" name="hero_title_destaque" value="<?php echo htmlspecialchars($data['hero_title_destaque'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Fim do Título</label>
                <input type="text" name="hero_title_2" value="<?php echo htmlspecialchars($data['hero_title_2'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Subtítulo (O pequeno texto abaixo do título)</label>
                <span class="helper-text">Pressione "Enter" para pular de linha. (Nenhuma tag HTML é necessária em todo o painel!)</span>
                <textarea name="hero_subtitle"><?php echo htmlspecialchars(str_replace('<br>', "\n", $data['hero_subtitle'])); ?></textarea>
            </div>
        </div>

        <!-- Tab 2: Sobre -->
        <div id="tab-sobre" class="tab-content">
            <h2>Sua Biografia & Especialidades</h2>
            
            <div class="form-group">
                <label>Sua Foto Oficial (Perfil Sobre Mim)</label>
                <div class="file-upload-wrapper">
                    <?php if(!empty($data['sobre_foto_path'])): ?>
                        <img src="<?php echo htmlspecialchars($data['sobre_foto_path']); ?>" alt="Foto Atual" class="img-preview" style="object-position: top;">
                    <?php endif; ?>
                    <div>
                        <label class="file-label">
                            <input type="file" name="sobre_foto" accept="image/*" class="file-input" onchange="updateFileName(this)">
                            Pressione para trocar Foto...
                        </label>
                        <div class="file-name">Nenhuma nova enviada (A atual está mantida).</div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>O começo da história (1º Parágrafo)</label>
                <textarea name="sobre_bio_1"><?php echo htmlspecialchars(strip_tags(str_replace('<br>', "\n", $data['sobre_bio_1']))); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Cursos, Formações e Títulos (2º Parágrafo)</label>
                <span class="helper-text">Lembre-se: Use a tecla "Enter" para listar os itens, um em cada linha.</span>
                <textarea name="sobre_bio_2" style="min-height: 200px;"><?php echo htmlspecialchars(strip_tags(str_replace('<br>', "\n", $data['sobre_bio_2']))); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>A sua Filosofia/Conclusão (3º Parágrafo)</label>
                <textarea name="sobre_bio_3" style="min-height: 150px;"><?php echo htmlspecialchars(strip_tags(str_replace('<br>', "\n", $data['sobre_bio_3']))); ?></textarea>
            </div>
        </div>

        <!-- Tab 3: Depoimentos -->
        <div id="tab-depoimentos" class="tab-content">
            <h2>Gerenciador de Avaliações</h2>
            <p style="color:#aaa; margin-bottom: 25px;">Aqui estão as avaliações da última sessão do site. Dê vida com provas sociais!</p>
            
            <div id="depoimentos-container">
                <?php foreach($data['depoimentos'] as $dep): ?>
                <div class="depoimento-row">
                    <div style="display:flex; justify-content: space-between; margin-bottom: 15px; align-items:center;">
                        <h3 style="margin: 0; color: #fff; font-size: 18px; font-weight: 500;">Paciente: <?php echo htmlspecialchars($dep['autor']) ?: 'Nova avaliação'; ?></h3>
                        <button type="button" class="remove-btn" onclick="confirmRemove(this)">
                            🗑 Excluir
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label>Nome do Paciente</label>
                        <input type="text" name="dep_autor[]" value="<?php echo htmlspecialchars($dep['autor']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Avaliação do Paciente no Google</label>
                        <textarea name="dep_texto[]" required style="min-height: 90px;"><?php echo htmlspecialchars(str_replace('<br>', "\n", $dep['texto'])); ?></textarea>
                    </div>
                    <div class="form-group" style="display:flex; gap: 15px; align-items: center; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px; border: 1px solid #333;">
                        <input type="number" name="dep_estrelas[]" value="<?php echo (int)$dep['estrelas']; ?>" min="1" max="5" required style="width: 80px; margin:0;" tabindex="-1">
                        <span style="color:var(--gold)">Quantas estrelas (1 a 5)? ★★★★★</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="btn-add" onclick="addDepoimento()">➕ Adicionar Nova Avaliação do Google</button>
        </div>

        <!-- Tab 4: FAQ -->
        <div id="tab-faq" class="tab-content">
            <h2>Perguntas Frequentes (FAQ)</h2>
            <p style="color:#aaa; margin-bottom: 25px;">Cadastre as principais dúvidas dos pacientes para quebrar objeções ativamente.</p>
            
            <div id="faq-container">
                <?php if(!empty($data['faq'])): foreach($data['faq'] as $faqItem): ?>
                <div class="depoimento-row">
                    <div style="display:flex; justify-content: space-between; margin-bottom: 15px; align-items:center;">
                        <h3 style="margin: 0; color: #fff; font-size: 18px; font-weight: 500;">Pergunta</h3>
                        <button type="button" class="remove-btn" onclick="confirmRemoveFaq(this)">
                            🗑 Excluir
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label>A pergunta (Ex: Como funciona o atendimento?)</label>
                        <input type="text" name="faq_pergunta[]" value="<?php echo htmlspecialchars($faqItem['pergunta']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>A resposta oficial</label>
                        <textarea name="faq_resposta[]" required style="min-height: 90px;"><?php echo htmlspecialchars(str_replace('<br>', "\n", $faqItem['resposta'])); ?></textarea>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
            
            <button type="button" class="btn-add" onclick="addFaq()">➕ Adicionar Nova Pergunta</button>
        </div>

        <!-- Tab 5: Geral e Configurações -->
        <div id="tab-geral" class="tab-content">
            <h2>Configurações Gerais Básicas</h2>
            
            <h3 style="margin-top:20px; color:#5EBEBD; font-family:'Jost',sans-serif; font-size: 18px;">Contato Rápido</h3>
            <div class="form-group">
                <label>Número do WhatsApp</label>
                <span class="helper-text">Inclua apenas números com DDI e DDD (Ex: 5531994945801)</span>
                <input type="number" name="whatsapp_number" value="<?php echo htmlspecialchars($data['whatsapp_number'] ?? '5531994945801'); ?>">
            </div>

            <h3 style="margin-top:40px; color:#5EBEBD; font-family:'Jost',sans-serif; font-size: 18px;">Redes Sociais — Instagram (Carrossel)</h3>
            <p style="color:#aaa; font-size:14px; margin-bottom: 20px;">Adicione vários links para criar um carrossel no site (recomendado até 4 itens).</p>
            <div id="insta-container">
                <?php 
                $urls = $data['insta_urls'] ?? (isset($data['insta_url']) && $data['insta_url'] ? [$data['insta_url']] : []);
                if(!empty($urls)): foreach($urls as $url): 
                ?>
                <div class="depoimento-row" style="margin-bottom: 10px; padding: 15px; background: rgba(0,0,0,0.2);">
                    <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom:10px;">
                        <h4 style="margin: 0; color: #fff; font-size: 16px;">Post do Instagram</h4>
                        <button type="button" class="remove-btn" onclick="this.parentElement.parentElement.remove()" style="padding: 4px 8px; font-size: 12px;">🗑 Remover</button>
                    </div>
                    <input type="text" name="insta_urls[]" value="<?php echo htmlspecialchars($url); ?>" placeholder="Ex: https://www.instagram.com/p/..." required>
                </div>
                <?php endforeach; endif; ?>
            </div>
            <button type="button" class="btn-add" onclick="addInsta()" style="margin-bottom: 30px;">➕ Adicionar Novo Post</button>

            <h3 style="margin-top:40px; color:#5EBEBD; font-family:'Jost',sans-serif; font-size: 18px;">Barra de Autoridade (Métricas)</h3>
            <p style="color:#aaa; font-size:14px;">Esses 3 blocos abaixo alteram aqueles números impactantes que ficam logo após o topo do site.</p>
            
            <div style="display:flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
                <div style="flex: 1; min-width: 250px; background: rgba(0,0,0,0.1); padding: 20px; border-radius: 8px;">
                    <label>Métrica 1 (Número)</label>
                    <input type="text" name="stat_1_num" value="<?php echo htmlspecialchars($data['stat_1_num'] ?? ''); ?>" style="margin-bottom: 10px;">
                    <label>Métrica 1 (Texto Menor)</label>
                    <input type="text" name="stat_1_text" value="<?php echo htmlspecialchars($data['stat_1_text'] ?? ''); ?>">
                </div>
                
                <div style="flex: 1; min-width: 250px; background: rgba(0,0,0,0.1); padding: 20px; border-radius: 8px;">
                    <label>Métrica 2 (Número)</label>
                    <input type="text" name="stat_2_num" value="<?php echo htmlspecialchars($data['stat_2_num'] ?? ''); ?>" style="margin-bottom: 10px;">
                    <label>Métrica 2 (Texto Menor)</label>
                    <input type="text" name="stat_2_text" value="<?php echo htmlspecialchars($data['stat_2_text'] ?? ''); ?>">
                </div>
                
                <div style="flex: 1; min-width: 250px; background: rgba(0,0,0,0.1); padding: 20px; border-radius: 8px;">
                    <label>Métrica 3 (Número)</label>
                    <input type="text" name="stat_3_num" value="<?php echo htmlspecialchars($data['stat_3_num'] ?? ''); ?>" style="margin-bottom: 10px;">
                    <label>Métrica 3 (Texto Menor)</label>
                    <input type="text" name="stat_3_text" value="<?php echo htmlspecialchars($data['stat_3_text'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <div class="save-bar">
            <a href="index.html" target="_blank" class="btn-secondary">🔗 Abrir Site (Prévia)</a>
            <button type="submit" class="btn-primary">💾 Salvar Alterações ao Vivo</button>
        </div>
    </form>
</div>

<template id="depoimento-template">
    <div class="depoimento-row">
        <div style="display:flex; justify-content: space-between; margin-bottom: 15px; align-items:center;">
            <h3 style="margin: 0; color: #fff; font-size: 18px; font-weight: 500;">Nova Avaliação</h3>
            <button type="button" class="remove-btn" onclick="this.parentElement.parentElement.remove()">
                🗑 Remover
            </button>
        </div>
        <div class="form-group">
            <label>Nome do Paciente</label>
            <input type="text" name="dep_autor[]" value="" required>
        </div>
        <div class="form-group">
            <label>Avaliação</label>
            <textarea name="dep_texto[]" required style="min-height: 90px;"></textarea>
        </div>
        <div class="form-group" style="display:flex; gap: 15px; align-items: center; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px; border: 1px solid #333;">
            <input type="number" name="dep_estrelas[]" value="5" min="1" max="5" required style="width: 80px; margin:0;" tabindex="-1">
            <span style="color:var(--gold)">Quantas estrelas (1 a 5)? ★★★★★</span>
        </div>
    </div>
</template>

<template id="faq-template">
    <div class="depoimento-row">
        <div style="display:flex; justify-content: space-between; margin-bottom: 15px; align-items:center;">
            <h3 style="margin: 0; color: #fff; font-size: 18px; font-weight: 500;">Nova Pergunta</h3>
            <button type="button" class="remove-btn" onclick="this.parentElement.parentElement.remove()">
                🗑 Remover
            </button>
        </div>
        <div class="form-group">
            <label>A pergunta</label>
            <input type="text" name="faq_pergunta[]" value="" required>
        </div>
        <div class="form-group">
            <label>A resposta</label>
            <textarea name="faq_resposta[]" required style="min-height: 90px;"></textarea>
        </div>
    </div>
</template>

<template id="insta-template">
    <div class="depoimento-row" style="margin-bottom: 10px; padding: 15px; background: rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom:10px;">
            <h4 style="margin: 0; color: #fff; font-size: 16px;">Novo Post do Instagram</h4>
            <button type="button" class="remove-btn" onclick="this.parentElement.parentElement.remove()" style="padding: 4px 8px; font-size: 12px;">🗑 Remover</button>
        </div>
        <input type="text" name="insta_urls[]" value="" placeholder="Cole a URL do post aqui..." required>
    </div>
</template>

<script>
    // Gerenciador de Estados da Interface (Troca Rápida de Abas)
    function openTab(evt, tabName) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        document.getElementById(tabName).classList.add('active');
        evt.currentTarget.classList.add('active');
        
        const y = document.querySelector('.tabs').getBoundingClientRect().top + window.scrollY - 100;
        window.scrollTo({top: y, behavior: 'smooth'});
    }

    // Gerenciador de Repeater para Inclusão Dinâmica de Elementos (Tabela)
    function addDepoimento() {
        const container = document.getElementById('depoimentos-container');
        const template = document.getElementById('depoimento-template');
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
        window.scrollBy({ top: 350, behavior: 'smooth' });
    }

    function addFaq() {
        const container = document.getElementById('faq-container');
        const template = document.getElementById('faq-template');
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
        window.scrollBy({ top: 300, behavior: 'smooth' });
    }

    function addInsta() {
        const container = document.getElementById('insta-container');
        const template = document.getElementById('insta-template');
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
    }

    // Confirmação de Segurança (Destruição de Dado Sensível)
    function confirmRemove(btn) {
        if(confirm('Tem certeza que quer EXCLUIR este depoimento permanentemente após salvar?')) {
            btn.parentElement.parentElement.remove();
        }
    }
    function confirmRemoveFaq(btn) {
        if(confirm('Tem certeza que quer remover esta pergunta e resposta?')) {
            btn.parentElement.parentElement.remove();
        }
    }

    // Feedback Visual para Inserção de Objetos Binários no UI
    function updateFileName(input) {
        let label = input.parentElement.nextElementSibling;
        if(input.files && input.files.length > 0) {
            label.innerHTML = "Substituindo por: <strong style='color:#5EBEBD;'>" + input.files[0].name + "</strong>";
        } else {
            label.textContent = "Nenhuma nova enviada (A atual está mantida).";
            label.style.color = "#a3c7d6";
        }
    }

    // Listeners do Ciclo de Vida do Aplicativo (Mensageria e Controle de Perda de Dados)
    window.onload = function() {
        const msg = "<?php echo $toastMsg; ?>";
        const type = "<?php echo $toastType; ?>";
        if (msg !== "") {
            const toast = document.getElementById('toast');
            document.getElementById('toast-text').innerText = msg;
            if(type === 'error') {
                toast.classList.add('toast-error');
                toast.querySelector('.icon').innerText = '❌';
            }
            toast.classList.add('show');
            setTimeout(() => { toast.classList.remove('show'); }, 4000);
        }
    };
    
    // Tratativa Global de Abandono de Formulário e Proteção Sessão
    let hasChanged = false;
    document.querySelectorAll('input, textarea').forEach(el => {
        el.addEventListener('change', () => hasChanged = true);
    });
    
    document.getElementById('adminForm').addEventListener('submit', () => {
        hasChanged = false; 
    });
    
    window.addEventListener('beforeunload', (e) => {
        if (hasChanged) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Auto-calculador de Altura em Tempo Real em Campos de Texto Expandidos
    const tx = document.getElementsByTagName("textarea");
    for (let i = 0; i < tx.length; i++) {
        tx[i].setAttribute("style", "height:" + (tx[i].scrollHeight) + "px;overflow-y:hidden;");
        tx[i].addEventListener("input", OnInput, false);
    }
    function OnInput() {
        this.style.height = "auto";
        this.style.height = (this.scrollHeight) + "px";
    }
</script>

</body>
</html>
