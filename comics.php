<?php
require_once 'includes_auth.php';

if(!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_data = $auth->getUserData($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HQ Verso - Sua plataforma de quadrinhos online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Estilos unificados - mantendo o melhor de cada arquivo */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            min-height: 100vh;
            padding: 0;
            margin: 0;
            overflow-x: hidden;
        }
        
        /* Modo Claro */
        body.light-mode {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            width: 100%;
            position: relative;
            overflow-x: hidden;
        }
        
        body {
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            margin-bottom: 30px;
            width: 100%;
            position: sticky;
            top: 0;
            background: linear-gradient(to bottom, rgba(26, 26, 46, 0.95), rgba(26, 26, 46, 0.85));
            backdrop-filter: blur(10px);
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        body.light-mode header {
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85));
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #e94560;
            text-decoration: none;
            letter-spacing: 1px;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-right: 20px;
            position: relative;
            z-index: 1000;
        }

        /* Botão de tema */
        .theme-toggle {
            background: transparent;
            border: none;
            color: #fff;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        body.light-mode .theme-toggle {
            color: #333;
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        body.light-mode .theme-toggle:hover {
            background: rgba(0, 0, 0, 0.1);
        }
        
        /* Navegação Principal */
        .main-nav {
            display: flex;
            gap: 20px;
        }
        
        .nav-item {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        
        .nav-item:hover {
            background: rgba(233, 69, 96, 0.2);
            color: #e94560;
        }
        
        /* Container de Busca MELHORADA */
        .search-container {
            position: relative;
        }

        .search-bar {
            display: flex;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 10px 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            align-items: center;
            min-width: 350px;
        }
        
        body.light-mode .search-bar {
            background: rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        
        .search-bar:focus-within {
            border-color: #e94560;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 15px rgba(233, 69, 96, 0.2);
        }
        
        body.light-mode .search-bar:focus-within {
            background: rgba(0, 0, 0, 0.08);
        }
        
        .search-bar input {
            background: transparent;
            border: none;
            color: white;
            padding: 5px 10px;
            width: 100%;
            outline: none;
            font-size: 16px;
        }
        
        body.light-mode .search-bar input {
            color: #333;
        }
        
        .search-bar input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        body.light-mode .search-bar input::placeholder {
            color: #718096;
        }

        .search-bar button {
            background: transparent;
            border: none;
            color: #e94560;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .search-bar button:hover {
            background: rgba(233, 69, 96, 0.2);
            transform: scale(1.1);
        }

        /* Modal de Busca */
        .search-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 10000;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .search-modal.active {
            display: block;
            opacity: 1;
        }

        .search-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(26, 26, 46, 0.95);
        }

        body.light-mode .search-modal-header {
            background: rgba(255, 255, 255, 0.95);
            border-bottom: 1px solid #e2e8f0;
        }

        .search-modal-header h3 {
            color: #e94560;
            margin: 0;
            font-size: 1.5rem;
        }

        .close-search {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        body.light-mode .close-search {
            color: #333;
        }

        .close-search:hover {
            background: rgba(233, 69, 96, 0.2);
        }

        .search-modal-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
        }

        /* Campo de busca no modal MELHORADO */
        .search-modal-input-container {
            position: relative;
            margin-bottom: 20px;
        }

        .search-modal-input {
            width: 100%;
            padding: 15px 20px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            outline: none;
            transition: all 0.3s ease;
        }

        body.light-mode .search-modal-input {
            background: rgba(0, 0, 0, 0.05);
            border: 2px solid #e2e8f0;
            color: #333;
        }

        .search-modal-input:focus {
            border-color: #e94560;
            background: rgba(255, 255, 255, 0.15);
        }

        body.light-mode .search-modal-input:focus {
            background: rgba(0, 0, 0, 0.08);
        }

        .search-modal-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        body.light-mode .search-modal-input::placeholder {
            color: #718096;
        }

        /* Estatísticas e Abas */
        .search-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        body.light-mode .search-stats {
            border-bottom: 1px solid #e2e8f0;
        }

        #resultsCount {
            font-size: 1rem;
            opacity: 0.8;
        }

        .search-tabs {
            display: flex;
            gap: 10px;
        }

        .tab-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        body.light-mode .tab-btn {
            background: rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            color: #333;
        }

        .tab-btn.active {
            background: #e94560;
            border-color: #e94560;
        }

        .tab-btn:hover:not(.active) {
            background: rgba(233, 69, 96, 0.3);
        }

        /* Container de Resultados */
        .search-results-container {
            flex: 1;
            overflow: hidden;
            position: relative;
        }

        .results-section {
            display: none;
            height: 100%;
            overflow-y: auto;
        }

        .results-section.active {
            display: block;
        }

        /* Grid de Resultados */
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding: 10px 0;
        }

        /* Estados Vazios */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            opacity: 0.5;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #e94560;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin: 0;
        }

        /* Cards no Modal */
        .search-comic-card, .search-user-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 15px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }

        body.light-mode .search-comic-card, 
        body.light-mode .search-user-card {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #e2e8f0;
        }

        .search-comic-card:hover, .search-user-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-3px);
            border-color: rgba(233, 69, 96, 0.3);
        }

        body.light-mode .search-comic-card:hover,
        body.light-mode .search-user-card:hover {
            background: white;
            border-color: #e94560;
        }

        .search-comic-card {
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }

        .search-comic-cover {
            width: 80px;
            height: 120px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .search-comic-info {
            flex: 1;
        }

        .search-comic-title {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 1rem;
            color: white;
        }

        body.light-mode .search-comic-title {
            color: #333;
        }

        .search-comic-meta {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-bottom: 3px;
        }

        .search-comic-rating {
            color: #ffd700;
            font-size: 0.8rem;
            margin-top: 5px;
        }

        .search-user-card {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e94560 0%, #d8345f 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }

        .search-user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .search-user-info {
            flex: 1;
        }

        .search-user-name {
            font-weight: 600;
            margin-bottom: 3px;
            color: white;
        }

        body.light-mode .search-user-name {
            color: #333;
        }

        .search-user-email {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-bottom: 5px;
        }

        .search-user-stats {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        .search-user-role {
            display: inline-block;
            padding: 2px 8px;
            background: rgba(233, 69, 96, 0.3);
            border-radius: 10px;
            font-size: 0.7rem;
            margin-top: 5px;
        }

        /* Botão de seguir no modal */
        .search-user-actions {
            margin-top: 10px;
        }

        .btn-follow {
            padding: 6px 12px;
            border: 1px solid #e94560;
            background: transparent;
            color: #e94560;
            border-radius: 15px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-follow:hover {
            background: rgba(233, 69, 96, 0.1);
        }

        .btn-follow.following {
            background: #e94560;
            color: white;
        }

        .btn-follow.following:hover {
            background: #d8345f;
        }

        /* Footer da Busca */
        .search-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 20px;
        }

        body.light-mode .search-footer {
            border-top: 1px solid #e2e8f0;
        }

        .advanced-search-link {
            color: #e94560;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s ease;
        }

        .advanced-search-link:hover {
            color: #d8345f;
        }

        .search-tips {
            font-size: 0.8rem;
            opacity: 0.7;
        }

        /* Loading State */
        .search-loading {
            text-align: center;
            padding: 40px;
        }

        .search-loading i {
            font-size: 2rem;
            color: #e94560;
            margin-bottom: 15px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Categorias */
        .categories {
            display: flex;
            gap: 12px;
            margin: 30px 0;
            flex-wrap: wrap;
            padding: 15px;
            background: rgba(15, 52, 96, 0.2);
            border-radius: 15px;
            justify-content: center;
        }
        
        .category {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        body.light-mode .category {
            background: rgba(0, 0, 0, 0.05);
            color: #4a5568;
        }
        
        .category i {
            font-size: 1.1em;
            opacity: 0.8;
        }
        
        .category.active {
            background: #e94560;
            color: white;
        }
        
        .category:hover {
            background: rgba(233, 69, 96, 0.7);
        }
        
        .comic-counter {
            text-align: center;
            margin: 10px 0 20px 0;
            font-size: 14px;
            opacity: 0.8;
            font-weight: 500;
        }
        
        body.light-mode .comic-counter {
            color: #4a5568;
        }
        
        .featured-comic {
            position: relative;
            height: 500px;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 40px;
            background: linear-gradient(135deg, #0f3460 0%, #1a1a2e 100%);
            background-image: url('https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT_1OXqNiK7qA0H461zQkADUkI6EwQIiExA3w&s');
            background-size: cover;
            background-position: center;
        }
        
        .featured-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.9));
            padding: 40px;
            color: white;
        }
        
        .featured-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .featured-description {
            font-size: 1.1rem;
            margin-bottom: 20px;
            opacity: 0.9;
            max-width: 600px;
        }
        
        .featured-actions {
            display: flex;
            gap: 15px;
        }
        
        .comics-section {
            margin-bottom: 60px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .section-title {
            font-size: 1.8rem;
            margin-bottom: 25px;
            font-weight: 600;
            color: #e94560;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        body.light-mode .section-title {
            color: #2d3748;
        }
        
        .section-title i {
            font-size: 1.4rem;
            opacity: 0.9;
        }
        
        /* Carrossel Container */
        .carousel-container {
            position: relative;
            margin-bottom: 20px;
        }
        
        .comics-carousel {
            display: flex;
            overflow-x: auto;
            scroll-behavior: smooth;
            gap: 25px;
            padding: 20px 10px;
            scrollbar-width: thin;
            scrollbar-color: #e94560 rgba(255, 255, 255, 0.1);
            -webkit-mask-image: linear-gradient(to right, black 80%, transparent 100%);
            mask-image: linear-gradient(to right, black 80%, transparent 100%);
        }
        
        .comics-carousel::-webkit-scrollbar {
            height: 8px;
        }
        
        .comics-carousel::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        
        body.light-mode .comics-carousel::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .comics-carousel::-webkit-scrollbar-thumb {
            background: #e94560;
            border-radius: 4px;
        }
        
        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(15, 52, 96, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            opacity: 0.7;
        }
        
        .carousel-nav:hover {
            background: rgba(15, 52, 96, 1);
            opacity: 1;
            transform: translateY(-50%) scale(1.1);
        }
        
        .carousel-nav.prev {
            left: 10px;
        }
        
        .carousel-nav.next {
            right: 10px;
        }
        
        .comic-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
            min-width: 250px;
            height: 420px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        body.light-mode .comic-card {
            background: rgba(255, 255, 255, 0.8);
            color: #333;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .comic-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        body.light-mode .comic-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .read-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e94560;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            z-index: 2;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .comic-card:hover .read-badge {
            opacity: 1;
        }
        
        .comic-cover {
            width: 100%;
            height: 300px;
            object-fit: cover;
            display: block;
        }
        
        .card-bottom {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex: 1;
            background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0.4));
            backdrop-filter: blur(5px);
        }
        
        .title-meta {
            flex: 1;
            color: #fff;
        }
        
        .comic-title {
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 1rem;
            line-height: 1.4;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .comic-meta {
            font-size: 0.8rem;
            opacity: 0.7;
        }
        
        body.light-mode .comic-meta {
            color: #718096;
        }
        
        .card-actions {
            margin-left: 10px;
        }
        
        .btn {
            padding: 8px 12px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            border: none;
            font-size: 0.8rem;
            text-align: center;
        }
        
        .btn-primary {
            background: #e94560;
            color: white;
        }
        
        .btn-primary:hover {
            background: #d8345f;
        }
        
        .btn-play {
            background: #e94560;
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #e94560;
            color: #e94560;
            padding: 6px 10px;
        }
        
        body.light-mode .btn-outline {
            color: #e94560;
        }
        
        .btn-outline:hover {
            background: rgba(233, 69, 96, 0.1);
        }
        
        .progress {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: rgba(255, 255, 255, 0.2);
            overflow: hidden;
            z-index: 5;
        }
        
        body.light-mode .progress {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(to right, #e94560, #ff6b81);
            transition: width 0.5s ease;
            position: relative;
            border-radius: 0 3px 3px 0;
            box-shadow: 0 0 10px rgba(233, 69, 96, 0.5);
        }
        
        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                90deg,
                rgba(255,255,255,0) 0%,
                rgba(255,255,255,0.3) 50%,
                rgba(255,255,255,0) 100%
            );
            animation: shine 2s infinite;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .continue-info {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            display: none;
        }
        
        .info-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .info-text {
            flex: 1;
        }
        
        .info-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .info-meta {
            font-size: 0.8rem;
            opacity: 0.7;
        }
        
        .info-progress {
            width: 150px;
            margin-left: 20px;
        }
        
        .progress-track {
            height: 6px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress-indicator {
            height: 100%;
            background: #e94560;
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        .user-avatar {
            position: relative;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #e94560;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-avatar:hover {
            transform: scale(1.05);
        }
        
        .user-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: rgba(26, 26, 46, 0.95);
            border-radius: 8px;
            padding: 10px 0;
            min-width: 220px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            display: none;
            z-index: 1000;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }
        
        body.light-mode .user-dropdown {
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .user-dropdown a {
            display: block;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        
        body.light-mode .user-dropdown a {
            color: #333;
        }
        
        .user-dropdown a:hover {
            background: rgba(233, 69, 96, 0.2);
        }
        
        .user-dropdown.active {
            display: block;
        }
        
        .browser-info-link {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(15, 52, 96, 0.8);
            color: white;
            padding: 12px 15px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        body.light-mode .browser-info-link {
            background: rgba(233, 69, 96, 0.8);
            color: white;
        }
        
        .browser-info-link:hover {
            background: rgba(15, 52, 96, 1);
            transform: translateY(-2px);
        }
        
        body.light-mode .browser-info-link:hover {
            background: rgba(233, 69, 96, 1);
        }
        
        .theme-toggle {
            position: fixed;
            bottom: 80px;
            right: 20px;
            background: rgba(15, 52, 96, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4);
        }
        
        body.light-mode .theme-toggle {
            background: rgba(233, 69, 96, 0.8);
            color: white;
        }
        
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        .no-comics {
            text-align: center;
            padding: 40px;
            opacity: 0.7;
            font-style: italic;
        }

        /* Modal de detalhe do quadrinho */
        .comic-modal-overlay{
            position:fixed;
            inset:0;
            background:rgba(0,0,0,0.7);
            display:none;
            align-items:center;
            justify-content:center;
            z-index:9999;
            padding:20px
        }
        .comic-modal{
            background:#0f1724;
            color:#fff;
            max-width:1000px;
            width:100%;
            border-radius:8px;
            overflow:hidden;
            box-shadow:0 10px 40px rgba(0,0,0,0.6);
            display:flex;
            gap:20px
        }
        .comic-modal .modal-cover{
            width:320px;
            flex:0 0 320px;
            height:480px;
            object-fit:cover;
            background:#111
        }
        .comic-modal .modal-body{
            padding:24px;
            flex:1;
            display:flex;
            flex-direction:column
        }
        .comic-modal .modal-title{
            font-size:1.5rem;
            margin:0 0 6px
        }
        .comic-modal .modal-meta{
            color:#9aa3b2;
            margin-bottom:12px
        }
        .comic-modal .modal-description{
            flex:1;
            line-height:1.5;
            color:#d1d9e6
        }
        .comic-modal .modal-actions{
            display:flex;
            gap:10px;
            margin-top:16px
        }
        .comic-modal .btn{
            padding:10px 14px;
            border-radius:6px;
            border:none;
            cursor:pointer
        }
        .comic-modal .btn-primary{
            background:#e94560;
            color:#fff
        }
        .comic-modal .btn-outline{
            background:transparent;
            color:#e94560;
            border:1px solid rgba(233,69,96,0.25)
        }
        .comic-modal-close{
            position:absolute;
            right:18px;
            top:14px;
            background:transparent;
            border:none;
            color:#fff;
            font-size:20px;
            cursor:pointer
        }
        @media(max-width:800px){
            .comic-modal{
                flex-direction:column
            }
            .comic-modal .modal-cover{
                width:100%;
                height:300px;
                flex:auto
            }
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 15px;
            }
            
            .header-right {
                width: 100%;
                justify-content: space-between;
            }
            
            .search-bar {
                min-width: 250px;
            }
            
            .search-modal-content {
                padding: 10px;
            }
            
            .search-modal-header {
                padding: 15px 20px;
            }
            
            .results-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .search-stats {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .search-tabs {
                justify-content: center;
            }
            
            .search-footer {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            /* Adicione isso no final do CSS existente */

.comments-btn {
    padding: 6px 8px !important;
    font-size: 0.8rem !important;
    margin-left: 5px;
}

.card-actions {
    display: flex;
    gap: 5px;
}
            
            .featured-comic {
                height: 300px;
            }
            
            .featured-title {
                font-size: 1.8rem;
            }
            
            .featured-description {
                font-size: 1rem;
            }
            
            .comics-carousel {
                gap: 15px;
            }
            
            .comic-card {
                min-width: 160px;
            }
            
            .comic-cover {
                height: 220px;
            }
            
            .carousel-nav {
                width: 35px;
                height: 35px;
            }
        }

        @media (max-width: 480px) {
            .search-bar {
                min-width: 200px;
            }
            
            .search-modal-input {
                padding: 12px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Botão do Tema MANTIDO -->
    <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </button>
    
    <div class="container comics-page" id="comicsPage">
        <header>
            <div class="header-left">
                <a href="#" class="logo">HQ VERSO</a>
            </div>
            <div class="header-right">
                <div class="search-container">
                    <div class="search-bar" id="searchBar">
                        <input type="text" placeholder="Buscar quadrinhos, usuários, autores..." aria-label="Buscar" id="searchInput">
                        <button type="button" aria-label="Buscar" id="searchButton">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <!-- Modal de Busca MELHORADO -->
                    <div class="search-modal" id="searchModal">
                        <div class="search-modal-header">
                            <h3>Resultados da Busca</h3>
                            <button class="close-search" id="closeSearch">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="search-modal-content">
                            <!-- Campo de busca adicionado no modal -->
                            <div class="search-modal-input-container">
                                <input type="text" 
                                       class="search-modal-input" 
                                       id="searchModalInput" 
                                       placeholder="Digite para buscar quadrinhos, usuários, autores..."
                                       autocomplete="off">
                            </div>
                            
                            <div class="search-stats" id="searchStats">
                                <span id="resultsCount">Digite para buscar</span>
                                <div class="search-tabs" id="searchTabs">
                                    <button class="tab-btn active" data-tab="all">Todos</button>
                                    <button class="tab-btn" data-tab="comics">Quadrinhos</button>
                                    <button class="tab-btn" data-tab="users">Usuários</button>
                                </div>
                            </div>
                            
                            <div class="search-results-container">
                                <div class="results-section active" id="resultsAll">
                                    <div class="empty-state" id="emptyAll">
                                        <i class="fas fa-search"></i>
                                        <p>Digite algo para buscar quadrinhos e usuários</p>
                                    </div>
                                    <div class="results-grid" id="resultsGridAll"></div>
                                </div>
                                
                                <div class="results-section" id="resultsComics">
                                    <div class="empty-state" id="emptyComics">
                                        <i class="fas fa-book"></i>
                                        <p>Nenhum quadrinho encontrado</p>
                                    </div>
                                    <div class="results-grid" id="resultsGridComics"></div>
                                </div>
                                
                                <div class="results-section" id="resultsUsers">
                                    <div class="empty-state" id="emptyUsers">
                                        <i class="fas fa-users"></i>
                                        <p>Nenhum usuário encontrado</p>
                                    </div>
                                    <div class="results-grid" id="resultsGridUsers"></div>
                                </div>
                            </div>
                            
                            <div class="search-footer">
                                <div class="search-tips">
                                    <strong>Dica:</strong> Tente buscar por título, autor, editora ou nome de usuário
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="user-avatar" id="userAvatar">
                    <?php 
                    $user_data = $auth->getUserData($_SESSION['user_id']);
                    $initial = strtoupper(substr($user_data['username'], 0, 1));
                    
                    if($user_data['avatar'] && file_exists($user_data['avatar'])): 
                    ?>
                        <img src="<?php echo $user_data['avatar']; ?>" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <span class="avatar-letter"><?php echo $initial; ?></span>
                    <?php endif; ?>
                    
                    <div class="user-dropdown" id="userDropdown">
                        <a href="perfil.php"><i class="fas fa-user"></i> Meu Perfil</a>
                        <?php if($auth->isAdmin()): ?>
                            <a href="admin.php"><i class="fas fa-cog"></i> Painel Admin</a>
                        <?php endif; ?>
                        <a href="detection.html"><i class="fas fa-info-circle"></i> Info do Navegador</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="categories">
            <div class="category active" data-category="all">
                <i class="fas fa-th-large"></i> Todos
            </div> 
            <div class="category" data-category="super-herois">
                <i class="fas fa-mask"></i> Super-heróis
            </div>
            <div class="category" data-category="manga">
                <i class="fas fa-book-open"></i> Mangá
            </div>
            <div class="category" data-category="graphic-novels">
                <i class="fas fa-book"></i> Graphic Novels
            </div>
            <div class="category" data-category="classicos">
                <i class="fas fa-star"></i> Clássicos
            </div>
            <div class="category" data-category="indie">
                <i class="fas fa-paint-brush"></i> Indie
            </div>
            <div class="category" data-category="favoritos">
                <i class="fas fa-heart"></i> Favoritos
            </div>
        </div>
        
        <div class="comic-counter" id="comicCounter">Carregando quadrinhos...</div>
        
        <div class="featured-comic">
            <div class="featured-overlay">
                <h2 class="featured-title">Batman: O Cavaleiro das Trevas</h2>
                <p class="featured-description">A obra-prima de Frank Miller que redefiniu o Batman para sempre.</p>
                <div class="featured-actions">
                    <button class="btn btn-play" onclick="window.location.href='leitor.html?comic=batman&title=Batman: O Cavaleiro das Trevas'">
                        <i class="fas fa-play"></i> Ler agora
                    </button>
                    <button class="btn btn-outline">
                        <i class="fas fa-plus"></i> Minha lista
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Seção Continuar Lendo -->
        <div class="comics-section continue-row">
            <h3 class="section-title">Continuar Lendo</h3>
            <div class="carousel-container">
                <div class="comics-carousel" id="continueReading">
                    <div class="no-comics">Nenhum quadrinho em progresso</div>
                </div>
                <button class="carousel-nav prev">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>
                <button class="carousel-nav next">
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
            <div class="continue-info" id="continueInfo">
                <div class="info-inner">
                    <div class="info-text">
                        <div class="info-title" id="infoTitle">Selecione um quadrinho</div>
                        <div class="info-meta" id="infoMeta">Para ver detalhes</div>
                    </div>
                    <div class="info-progress">
                        <div class="progress-track">
                            <div class="progress-indicator" id="infoProgress" style="width:0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Seção Recomendados -->
        <div class="comics-section">
            <h3 class="section-title">Recomendados para você</h3>
            <div class="carousel-container">
                <div class="comics-carousel" id="recommendedComics">
                    <div class="no-comics">Carregando recomendações...</div>
                </div>
                <button class="carousel-nav prev">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>
                <button class="carousel-nav next">
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        
        <!-- Seção Clássicos (genérica) -->
<div class="comics-section">
    <h3 class="section-title">Clássicos</h3>
    <div class="carousel-container">
        <div class="comics-carousel" id="classics">
            <div class="no-comics">Carregando clássicos...</div>
        </div>
        <button class="carousel-nav prev">
            <i class="fas fa-chevron-left" aria-hidden="true"></i>
        </button>
        <button class="carousel-nav next">
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
        </button>
    </div>
</div>

<!-- Seção Clássicos da DC -->
<div class="comics-section">
    <h3 class="section-title">Clássicos da DC</h3>
    <div class="carousel-container">
        <div class="comics-carousel" id="dcClassics">
            <div class="no-comics">Carregando clássicos da DC...</div>
        </div>
        <button class="carousel-nav prev">
            <i class="fas fa-chevron-left" aria-hidden="true"></i>
        </button>
        <button class="carousel-nav next">
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
        </button>
    </div>
</div>
        
        <!-- Seção Mangás Populares -->
        <div class="comics-section">
            <h3 class="section-title">Mangás Populares</h3>
            <div class="carousel-container">
                <div class="comics-carousel" id="popularManga">
                    <div class="no-comics">Carregando mangás...</div>
                </div>
                <button class="carousel-nav prev">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>
                <button class="carousel-nav next">
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        
        <!-- Seção Graphic Novels -->
        <div class="comics-section">
            <h3 class="section-title">Graphic Novels</h3>
            <div class="carousel-container">
                <div class="comics-carousel" id="graphicNovels">
                    <div class="no-comics">Carregando graphic novels...</div>
                </div>
                <button class="carousel-nav prev">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>
                <button class="carousel-nav next">
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>

    <a href="detection.html" class="browser-info-link">
        <i class="fas fa-info-circle"></i> Info Navegador
    </a>

    <!-- Modal de detalhe do quadrinho -->
    <!-- Modal de detalhe do quadrinho - ATUALIZADO -->
<div id="comicModalOverlay" class="comic-modal-overlay" aria-hidden="true" role="dialog" aria-labelledby="comicModalTitle">
    <div class="comic-modal" role="document">
        <button class="comic-modal-close" id="comicModalClose" aria-label="Fechar">&times;</button>
        <img src="" alt="Capa" class="modal-cover" id="comicModalCover">
        <div class="modal-body">
            <h2 id="comicModalTitle" class="modal-title">Título do Quadrinho</h2>
            <div id="comicModalMeta" class="modal-meta">Meta / Autor / Ano</div>
            <p id="comicModalDescription" class="modal-description">Sinopse do quadrinho será exibida aqui.</p>
            <div class="modal-actions">
                <button id="comicModalRead" class="btn btn-primary">Ler agora</button>
                <button id="comicModalAdd" class="btn btn-outline">Adicionar à minha lista</button>
                <!-- NOVO BOTÃO DE COMENTÁRIOS -->
                <button id="comicModalComments" class="btn btn-outline" style="display: none;">
                    <i class="fas fa-comments"></i> Ver Comentários
                </button>
            </div>
        </div>
    </div>
</div>

    <script>
    // Dados de usuários para busca (fallback)
    const usersData = [
        {
            id: 1,
            username: "comic_lover",
            email: "comic.lover@email.com",
            role: "Leitor",
            comics_count: 42,
            favorites_count: 15,
            followers_count: 28,
            avatar: ""
        },
        {
            id: 2,
            username: "manga_fan",
            email: "manga.fan@email.com",
            role: "Leitor",
            comics_count: 28,
            favorites_count: 8,
            followers_count: 15,
            avatar: ""
        },
        {
            id: 3,
            username: "art_creator",
            email: "art.creator@email.com",
            role: "Artista",
            comics_count: 12,
            favorites_count: 32,
            followers_count: 45,
            avatar: ""
        },
        {
            id: 4,
            username: "dc_collector",
            email: "dc.collector@email.com",
            role: "Colecionador",
            comics_count: 67,
            favorites_count: 23,
            followers_count: 32,
            avatar: ""
        },
        {
            id: 5,
            username: "marvel_fan",
            email: "marvel.fan@email.com",
            role: "Fã",
            comics_count: 35,
            favorites_count: 18,
            followers_count: 21,
            avatar: ""
        }
    ];

    // Dados locais como fallback
    const comicsData = {
        "all": [
            {
                id: 1,
                title: "Homem-Aranha: A Última Caçada",
                cover: "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTg9az9jtGfQoj20u_4hUEeTQXvXvX9i0W2KPKw&s",
                meta: "Capítulo 3",
                progress: 20,
                categories: ["super-herois", "classicos"],
                description: "A clássica história onde o Homem-Aranha enfrenta seu maior desafio."
            },
            // ... outros quadrinhos como fallback
        ]
    };

    // Variável global para armazenar quadrinhos do banco
    let allComics = [];

    // Função para criar card de quadrinho
    // Função para criar card de quadrinho - ATUALIZADA
function createComicCard(comic, showProgress = false) {
    const placeholder = `https://via.placeholder.com/200x300/1a1a2e/e94560?text=${encodeURIComponent(comic.title.substring(0, 15))}`;
    
    return `
        <div class="comic-card" data-id="${comic.id}" data-categories="${comic.categories ? comic.categories.join(',') : 'all'}">
            <span class="read-badge">Ler agora</span>
            <img src="${comic.cover}" 
                 alt="Capa do quadrinho ${comic.title}" 
                 class="comic-cover"
                 onerror="this.src='${placeholder}'">
            <div class="card-bottom">
                <div class="title-meta">
                    <div class="comic-title">${comic.title}</div>
                    <div class="comic-meta">${comic.meta || 'Quadrinho'}</div>
                </div>
                <div class="card-actions">
                    <button class="btn btn-outline add-list" title="Adicionar à lista">+</button>
                    <!-- NOVO: Ícone de comentários -->
                    <button class="btn btn-outline comments-btn" title="Ver comentários" onclick="event.stopPropagation(); window.location.href='comentarios.php?comic_id=${comic.id}'">
                        <i class="fas fa-comments"></i>
                    </button>
                </div>
            </div>
            ${showProgress && comic.progress ? `
            <div class="progress"><div class="progress-bar" style="width:${comic.progress}%"></div></div>
            ` : ''}
        </div>
    `;
}

    // Função para criar card de quadrinho do banco de dados
    function createModalComicCard(comic) {
        const placeholder = `https://via.placeholder.com/80x120/1a1a2e/e94560?text=${encodeURIComponent(comic.title.substring(0, 10))}`;
        
        return `
            <div class="search-comic-card" data-id="${comic.id}" data-type="comic">
                <img src="${comic.cover}" 
                     alt="${comic.title}" 
                     class="search-comic-cover"
                     onerror="this.src='${placeholder}'">
                <div class="search-comic-info">
                    <div class="search-comic-title">${comic.title}</div>
                    <div class="search-comic-meta">${comic.meta}</div>
                    <div class="search-comic-meta">${comic.categories ? comic.categories.join(', ') : 'Geral'}</div>
                </div>
            </div>
        `;
    }

    // Função para criar card de usuário no modal
    function createModalUserCard(user, currentUserId = null) {
        const initial = user.username ? user.username.charAt(0).toUpperCase() : 'U';
        const isFollowing = user.is_following == 1 || user.is_following === true;
        const canFollow = currentUserId && currentUserId != user.id;
        
        return `
            <div class="search-user-card" data-id="${user.id}" data-type="user">
                <div class="search-user-avatar">
                    ${user.avatar ? `<img src="${user.avatar}" alt="${user.username}">` : `<span>${initial}</span>`}
                </div>
                <div class="search-user-info">
                    <div class="search-user-name">${user.username}</div>
                    <div class="search-user-email">${user.email}</div>
                    <div class="search-user-stats">
                        ${user.comics_count || 0} comics • ${user.followers_count || 0} seguidores
                    </div>
                    <div class="search-user-role">${user.role}</div>
                    ${canFollow ? `
                    <div class="search-user-actions">
                        <button class="btn-follow ${isFollowing ? 'following' : ''}" 
                                data-user-id="${user.id}"
                                onclick="toggleFollow(${user.id}, this)">
                            ${isFollowing ? '<i class="fas fa-user-check"></i> Seguindo' : '<i class="fas fa-user-plus"></i> Seguir'}
                        </button>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    // Sistema de Progresso de Leitura - INTEGRADO COM BANCO
    async function setupReadingProgress() {
        try {
            // Carregar quadrinhos do banco
            const dbComics = await loadComicsFromDatabase();
            
            // Carregar progresso do servidor
            const progressData = await loadServerProgress();
            
            // Combinar os dados
            allComics = combineComicsWithProgress(dbComics, progressData);
            
            // Atualizar a interface
            updateAllSections('all');
            updateContinueReadingSection();
            
            console.log('Sistema de progresso carregado:', allComics.length + ' quadrinhos');
        } catch (error) {
            console.error('Erro no setup de progresso:', error);
            // Fallback para dados locais
            allComics = comicsData.all;
            updateAllSections('all');
        }
    }

    // Função para carregar quadrinhos do banco de dados
    async function loadComicsFromDatabase() {
        try {
            const response = await fetch('get_comics_from_db.php');
            const data = await response.json();
            
            if (data.success && data.comics) {
                return data.comics;
            } else {
                console.warn('Não foi possível carregar quadrinhos do banco, usando dados locais');
                return comicsData.all;
            }
        } catch (error) {
            console.error('Erro ao carregar quadrinhos:', error);
            return comicsData.all;
        }
    }

    // Carregar progresso do servidor
    async function loadServerProgress() {
        try {
            const response = await fetch('get_all_progress.php');
            const data = await response.json();
            
            if (data.progresses) {
                return data.progresses;
            }
            return [];
        } catch (error) {
            console.error('Erro ao carregar progresso:', error);
            return [];
        }
    }

    // Função para combinar quadrinhos do banco com progresso
    function combineComicsWithProgress(dbComics, progressData) {
        return dbComics.map(comic => {
            const progressInfo = progressData.find(p => p.comic_id === comic.id);
            return {
                ...comic,
                progress: progressInfo ? progressInfo.progress_pct : 0,
                lastRead: progressInfo ? progressInfo.last_read_at : null
            };
        });
    }

    // Obter quadrinhos em progresso
    function getContinueReadingComics() {
        return allComics
            .filter(comic => comic.progress > 0 && comic.progress < 100)
            .sort((a, b) => {
                // Ordenar por último lido (mais recente primeiro)
                if (!a.lastRead && !b.lastRead) return 0;
                if (!a.lastRead) return 1;
                if (!b.lastRead) return -1;
                return new Date(b.lastRead) - new Date(a.lastRead);
            });
    }

    // Atualizar seção "Continuar Lendo"
    function updateContinueReadingSection() {
        const continueReadingComics = getContinueReadingComics();
        const continueSection = document.getElementById('continueReading');
        const continueInfo = document.getElementById('continueInfo');
        
        if (!continueSection) return;
        
        if (continueReadingComics.length === 0) {
            continueSection.innerHTML = '<div class="no-comics">Nenhum quadrinho em progresso. Comece a ler algum quadrinho!</div>';
            if (continueInfo) continueInfo.style.display = 'none';
            return;
        }
        
        // Renderizar quadrinhos em progresso
        renderComicsInSection('continueReading', continueReadingComics.slice(0, 6), true);
        
        // Atualizar info do primeiro quadrinho
        if (continueInfo) {
            const firstComic = continueReadingComics[0];
            updateContinueInfo(firstComic);
        }
        
        // Adicionar event listeners
        addComicCardEventListeners();
    }

    // Atualizar info do quadrinho em progresso
    function updateContinueInfo(comic) {
        const continueInfo = document.getElementById('continueInfo');
        const infoTitle = document.getElementById('infoTitle');
        const infoMeta = document.getElementById('infoMeta');
        const infoProgress = document.getElementById('infoProgress');
        
        if (comic && comic.progress > 0) {
            infoTitle.textContent = comic.title;
            infoMeta.textContent = `${comic.progress}% lido • ${comic.meta || 'Em progresso'}`;
            infoProgress.style.width = `${comic.progress}%`;
            continueInfo.style.display = 'block';
        } else {
            continueInfo.style.display = 'none';
        }
    }

    // Salvar progresso
    function saveReadingProgress(comicId, progress, currentPage = 1) {
        // Encontrar o quadrinho na lista
        const comicIndex = allComics.findIndex(c => c.id === comicId);
        if (comicIndex !== -1) {
            allComics[comicIndex].progress = progress;
            allComics[comicIndex].lastRead = new Date().toISOString();
        }
        
        // Salvar no servidor
        fetch('save_progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                comicId: comicId,
                progress: Math.min(progress, 100)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                console.log(`Progresso salvo: Quadrinho ${comicId} - ${progress}%`);
                // Atualizar a seção "Continuar Lendo"
                updateContinueReadingSection();
            }
        })
        .catch(error => {
            console.error('Erro ao salvar progresso:', error);
        });
    }

    // Sistema de filtro por categoria
    function filterComicsByCategory(category) {
        if (category === 'all') {
            return allComics;
        } else if (category === 'favoritos') {
            // Implemente lógica de favoritos se necessário
            return allComics.slice(0, 4);
        } else {
            return allComics.filter(comic => 
                comic.categories && comic.categories.includes(category)
            );
        }
    }

    // Renderizar quadrinhos em uma seção
    function renderComicsInSection(sectionId, comics, showProgress = false) {
        const section = document.getElementById(sectionId);
        if (!section) return;
        
        if (comics.length === 0) {
            section.innerHTML = '<div class="no-comics">Nenhum quadrinho encontrado</div>';
            return;
        }
        
        section.innerHTML = '';
        
        comics.forEach(comic => {
            section.innerHTML += createComicCard(comic, showProgress);
        });
        
        // Adicionar event listeners aos novos cards
        addComicCardEventListeners();
    }

    // Adicionar event listeners aos cards de quadrinhos
    function addComicCardEventListeners() {
        document.querySelectorAll('.comic-card').forEach(card => {
            // Remover event listeners antigos para evitar duplicação
            card.replaceWith(card.cloneNode(true));
        });

        // Adicionar novos event listeners
        document.querySelectorAll('.comic-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Se clicou no botão 'Ler agora', ir para o leitor
                if (e.target.closest('.read-badge')) {
                    const comicId = parseInt(this.getAttribute('data-id'), 10);
                    const comic = allComics.find(c => c.id === comicId);
                    if (comic) {
                        // Salvar progresso inicial
                        saveReadingProgress(comicId, 10);
                        window.location.href = `leitor.html?comic=${comicId}&title=${encodeURIComponent(comic.title)}`;
                    }
                    return;
                }
                
                // Ignorar cliques nos botões de ação
                if (e.target.closest('.card-actions') || e.target.tagName === 'BUTTON') return;
                
                // Abrir modal de detalhes
                const comicId = parseInt(this.getAttribute('data-id'), 10);
                const comic = allComics.find(c => c.id === comicId);
                if (comic) {
                    openComicModal(comic);
                }
            });
        });
    }

    // Atualizar todas as seções
    function updateAllSections(category) {
    const filteredComics = filterComicsByCategory(category);
    
    // Seção Continuar Lendo
    const continueReadingComics = getContinueReadingComics();
    if (continueReadingComics.length === 0) {
        renderComicsInSection('continueReading', [], true);
    }
    
    // Seção Recomendados
    const recommendedComics = filteredComics.slice(0, 6);
    renderComicsInSection('recommendedComics', recommendedComics);
    
    // Seção Clássicos (genérica)
    const classics = filteredComics.filter(comic => 
        comic.categories && comic.categories.includes('classicos')
    );
    renderComicsInSection('classics', classics.slice(0, 4));
    
    // Seção Clássicos da DC (MODIFICADA)
    const dcClassics = allComics.filter(comic => 
        isDCClassic(comic)
    );
    renderComicsInSection('dcClassics', dcClassics.slice(0, 6));
    
    // Seção Mangás
    const mangaComics = filteredComics.filter(comic => 
        comic.categories && comic.categories.includes('manga')
    );
    renderComicsInSection('popularManga', mangaComics.slice(0, 4));
    
    // Seção Graphic Novels
    const graphicNovels = filteredComics.filter(comic => 
        comic.categories && comic.categories.includes('graphic-novels')
    );
    renderComicsInSection('graphicNovels', graphicNovels.slice(0, 4));
    
    updateComicCount(filteredComics.length);
}

// Função para identificar quadrinhos clássicos da DC
function isDCClassic(comic) {
    // Lista de IDs dos quadrinhos da DC que são clássicos
    const dcClassicIds = [9, 10, 16, 24, 25, 26, 27]; // IDs dos quadrinhos da DC do seu banco
    
    // Verifica se o quadrinho está na lista de clássicos da DC
    return dcClassicIds.includes(comic.id);
}

    // Atualizar contador de quadrinhos
    function updateComicCount(count) {
        const counter = document.getElementById('comicCounter');
        if (counter) {
            counter.textContent = `${count} quadrinhos encontrados`;
        }
    }

    // Sistema de Busca Modal
    function setupModalSearch() {
        const searchInput = document.getElementById('searchInput');
        const searchButton = document.getElementById('searchButton');
        const searchModal = document.getElementById('searchModal');
        const closeSearch = document.getElementById('closeSearch');
        const searchModalInput = document.getElementById('searchModalInput');
        const searchTabs = document.querySelectorAll('.tab-btn');
        const resultsSections = document.querySelectorAll('.results-section');
        
        let currentResults = { comics: [], users: [] };
        let searchTimeout = null;
        let currentTab = 'all';
        
        // Abrir modal ao clicar no botão de busca ou no input
        searchButton.addEventListener('click', openSearchModal);
        searchInput.addEventListener('click', openSearchModal);
        
        // Busca por Enter no input principal
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                openSearchModal();
                if (this.value.trim()) {
                    searchModalInput.value = this.value;
                    performModalSearch(this.value);
                }
            }
        });
        
        // Fechar modal
        closeSearch.addEventListener('click', closeSearchModal);
        
        // Fechar modal ao clicar fora
        searchModal.addEventListener('click', function(e) {
            if (e.target === searchModal) {
                closeSearchModal();
            }
        });
        
        // Fechar com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && searchModal.classList.contains('active')) {
                closeSearchModal();
            }
        });
        
        // Busca em tempo real no modal
        searchModalInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (searchTerm.length < 2) {
                showEmptyState();
                return;
            }
            
            // Mostrar loading
            showLoadingState();
            
            searchTimeout = setTimeout(() => {
                performModalSearch(searchTerm);
            }, 500);
        });
        
        // Busca por Enter no modal
        searchModalInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm.length >= 2) {
                    clearTimeout(searchTimeout);
                    showLoadingState();
                    performModalSearch(searchTerm);
                }
            }
        });
        
        function openSearchModal() {
            searchModal.classList.add('active');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                searchModalInput.focus();
                // Copiar valor do input principal se existir
                if (searchInput.value.trim()) {
                    searchModalInput.value = searchInput.value;
                    performModalSearch(searchInput.value);
                }
            }, 100);
        }
        
        function closeSearchModal() {
            searchModal.classList.remove('active');
            document.body.style.overflow = '';
            searchModalInput.value = '';
            showEmptyState();
        }
        
        function performModalSearch(searchTerm) {
            // Buscar quadrinhos dos dados atuais
            const comicResults = allComics.filter(comic => 
                comic.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                (comic.meta && comic.meta.toLowerCase().includes(searchTerm.toLowerCase())) ||
                (comic.description && comic.description.toLowerCase().includes(searchTerm.toLowerCase())) ||
                (comic.categories && comic.categories.some(cat => cat.toLowerCase().includes(searchTerm.toLowerCase())))
            );
            
            // Buscar usuários VIA AJAX
            fetch(`search_users.php?q=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(userResults => {
                    currentResults = { comics: comicResults, users: userResults };
                    displayModalResults(currentResults, searchTerm);
                })
                .catch(error => {
                    console.error('Erro na busca de usuários:', error);
                    // Fallback para dados mockados
                    const userResults = usersData.filter(user =>
                        user.username.toLowerCase().includes(searchTerm.toLowerCase()) ||
                        user.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                        user.role.toLowerCase().includes(searchTerm.toLowerCase())
                    ).map(user => ({
                        ...user,
                        is_following: false,
                    }));
                    
                    currentResults = { comics: comicResults, users: userResults };
                    displayModalResults(currentResults, searchTerm);
                });
        }
        
        function displayModalResults(results, searchTerm) {
            const totalResults = results.comics.length + results.users.length;
            
            // Atualizar estatísticas
            document.getElementById('resultsCount').textContent = 
                `${totalResults} resultados para "${searchTerm}"`;
            
            // Atualizar todas as abas
            updateResultsDisplay(currentTab);
        }
        
        function updateResultsDisplay(activeTab) {
            const comicsGridAll = document.getElementById('resultsGridAll');
            const comicsGridComics = document.getElementById('resultsGridComics');
            const comicsGridUsers = document.getElementById('resultsGridUsers');
            
            const emptyAll = document.getElementById('emptyAll');
            const emptyComics = document.getElementById('emptyComics');
            const emptyUsers = document.getElementById('emptyUsers');
            
            // Limpar grids
            comicsGridAll.innerHTML = '';
            comicsGridComics.innerHTML = '';
            comicsGridUsers.innerHTML = '';
            
            // Mostrar/ocultar estados vazios
            emptyAll.style.display = currentResults.comics.length === 0 && currentResults.users.length === 0 ? 'block' : 'none';
            emptyComics.style.display = currentResults.comics.length === 0 ? 'block' : 'none';
            emptyUsers.style.display = currentResults.users.length === 0 ? 'block' : 'none';
            
            // Popular aba "Todos"
            if (currentResults.comics.length > 0) {
                currentResults.comics.slice(0, 6).forEach(comic => {
                    comicsGridAll.innerHTML += createModalComicCard(comic);
                });
            }
            
            if (currentResults.users.length > 0) {
                currentResults.users.slice(0, 4).forEach(user => {
                    comicsGridAll.innerHTML += createModalUserCard(user, <?php echo $_SESSION['user_id']; ?>);
                });
            }
            
            // Popular aba "Quadrinhos"
            if (currentResults.comics.length > 0) {
                currentResults.comics.forEach(comic => {
                    comicsGridComics.innerHTML += createModalComicCard(comic);
                });
            }
            
            // Popular aba "Usuários"
            if (currentResults.users.length > 0) {
                currentResults.users.forEach(user => {
                    comicsGridUsers.innerHTML += createModalUserCard(user, <?php echo $_SESSION['user_id']; ?>);
                });
            }
            
            // Adicionar event listeners para os cards
            addModalCardEventListeners();
        }
        
        function addModalCardEventListeners() {
            // Event listeners para cards de quadrinhos
            document.querySelectorAll('.search-comic-card').forEach(card => {
                card.addEventListener('click', function() {
                    const comicId = this.getAttribute('data-id');
                    const comic = currentResults.comics.find(c => c.id == comicId);
                    if (comic) {
                        closeSearchModal();
                        window.location.href = `leitor.html?comic=${comicId}&title=${encodeURIComponent(comic.title)}`;
                    }
                });
            });
            
            // Event listeners para cards de usuários
            document.querySelectorAll('.search-user-card').forEach(card => {
                card.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    closeSearchModal();
                    window.location.href = `perfil.php?user_id=${userId}`;
                });
            });
        }
        
        function showEmptyState() {
            document.getElementById('resultsCount').textContent = 'Digite para buscar';
            document.querySelectorAll('.results-grid').forEach(grid => grid.innerHTML = '');
            document.querySelectorAll('.empty-state').forEach(empty => empty.style.display = 'block');
        }
        
        function showLoadingState() {
            document.getElementById('resultsCount').textContent = 'Buscando...';
            document.querySelectorAll('.results-grid').forEach(grid => {
                grid.innerHTML = `
                    <div class="search-loading">
                        <i class="fas fa-spinner"></i>
                        <div>Carregando resultados...</div>
                    </div>
                `;
            });
        }
        
        // Trocar abas
        searchTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                currentTab = tabName;
                
                // Atualizar aba ativa
                searchTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Mostrar seção correspondente
                resultsSections.forEach(section => {
                    section.classList.remove('active');
                    if (section.id === `results${tabName.charAt(0).toUpperCase() + tabName.slice(1)}`) {
                        section.classList.add('active');
                    }
                });
                
                updateResultsDisplay(tabName);
            });
        });
    }

    // Sistema de Tema
    function setupTheme() {
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        
        // Verificar tema salvo
        const savedTheme = localStorage.getItem('theme') || localStorage.getItem('hq-verso-theme');
        
        if (savedTheme === 'light') {
            body.classList.add('light-mode');
            // Atualizar ícone
            const icon = themeToggle.querySelector('i');
            if (icon) {
                icon.classList.replace('fa-moon', 'fa-sun');
            }
        }
        
        // Alternar tema
        themeToggle.addEventListener('click', () => {
            body.classList.toggle('light-mode');
            const icon = themeToggle.querySelector('i');
            
            if (body.classList.contains('light-mode')) {
                icon.classList.replace('fa-moon', 'fa-sun');
                localStorage.setItem('theme', 'light');
                localStorage.setItem('hq-verso-theme', 'light');
            } else {
                icon.classList.replace('fa-sun', 'fa-moon');
                localStorage.setItem('theme', 'dark');
                localStorage.setItem('hq-verso-theme', 'dark');
            }
        });
    }

    // Sistema de categoria
    function setupCategoryFilter() {
        document.querySelectorAll('.category').forEach(category => {
            category.addEventListener('click', function() {
                document.querySelectorAll('.category').forEach(cat => cat.classList.remove('active'));
                this.classList.add('active');
                
                const categoryType = this.getAttribute('data-category');
                
                document.querySelectorAll('.comics-carousel').forEach(carousel => {
                    carousel.style.opacity = '0.7';
                    setTimeout(() => {
                        carousel.style.opacity = '1';
                    }, 300);
                });
                
                updateAllSections(categoryType);
            });
        });
    }

    // Sistema de navegação do carrossel
    function setupCarouselNavigation() {
        document.querySelectorAll('.carousel-container').forEach(container => {
            const carousel = container.querySelector('.comics-carousel');
            const prevBtn = container.querySelector('.carousel-nav.prev');
            const nextBtn = container.querySelector('.carousel-nav.next');
            
            if (!carousel) return;
            
            // Verificar estado inicial
            updateNavButtons(carousel, prevBtn, nextBtn);
            
            // Atualizar durante o scroll
            carousel.addEventListener('scroll', () => {
                updateNavButtons(carousel, prevBtn, nextBtn);
            });
            
            // Configurar botões
            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    const amount = Math.round(carousel.clientWidth * 0.8) || 300;
                    scrollCarousel(carousel.id, -amount);
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    const amount = Math.round(carousel.clientWidth * 0.8) || 300;
                    scrollCarousel(carousel.id, amount);
                });
            }
        });
    }

    function updateNavButtons(carousel, prevBtn, nextBtn) {
        if (!carousel || !prevBtn || !nextBtn) return;
        const scrollLeft = carousel.scrollLeft;
        const scrollWidth = carousel.scrollWidth;
        const clientWidth = carousel.clientWidth;
        
        // Botão anterior
        if (scrollLeft <= 10) {
            prevBtn.disabled = true;
            prevBtn.style.opacity = '0.3';
        } else {
            prevBtn.disabled = false;
            prevBtn.style.opacity = '0.9';
        }
        
        // Botão próximo
        if (scrollLeft + clientWidth >= scrollWidth - 10) {
            nextBtn.disabled = true;
            nextBtn.style.opacity = '0.3';
        } else {
            nextBtn.disabled = false;
            nextBtn.style.opacity = '0.9';
        }
    }

    function scrollCarousel(carouselId, amount) {
        const carousel = document.getElementById(carouselId);
        if (carousel) {
            carousel.scrollBy({ left: amount, behavior: 'smooth' });
        }
    }

    // Modal de detalhe do quadrinho
    // Modal de detalhe do quadrinho - ATUALIZADO
function openComicModal(comic) {
    const overlay = document.getElementById('comicModalOverlay');
    if (!overlay || !comic) return;
    const cover = document.getElementById('comicModalCover');
    const title = document.getElementById('comicModalTitle');
    const meta = document.getElementById('comicModalMeta');
    const desc = document.getElementById('comicModalDescription');
    const readBtn = document.getElementById('comicModalRead');
    const addBtn = document.getElementById('comicModalAdd');
    
    cover.src = comic.cover || '';
    cover.alt = `Capa do quadrinho ${comic.title || ''}`;
    title.textContent = comic.title || '';
    meta.textContent = comic.meta || '';
    desc.textContent = comic.description || 'Sem sinopse disponível.';
    
    // ações dos botões
    readBtn.onclick = function() {
        saveReadingProgress(comic.id, 10); // Iniciar com 10% de progresso
        window.location.href = `leitor.html?comic=${encodeURIComponent(comic.id)}&title=${encodeURIComponent(comic.title)}`;
    };
    
    addBtn.onclick = function() {
        addBtn.textContent = 'Adicionado';
        addBtn.disabled = true;
        // Aqui você pode adicionar lógica para adicionar à lista
    };
    
    // NOVO: Botão de comentários
    const commentsBtn = document.getElementById('comicModalComments');
    if (commentsBtn) {
        commentsBtn.onclick = function() {
            window.location.href = `comentarios.php?comic_id=${comic.id}`;
        };
        commentsBtn.style.display = 'block';
    }
    
    overlay.style.display = 'flex';
    overlay.setAttribute('aria-hidden', 'false');
    setTimeout(() => {
        readBtn.focus();
    }, 120);
}
    
    function closeComicModal() {
        const overlay = document.getElementById('comicModalOverlay');
        if (!overlay) return;
        overlay.style.display = 'none';
        overlay.setAttribute('aria-hidden', 'true');
    }
    
    function setupComicModalHandlers() {
        // Fechar pelo botão e clicando fora
        const overlay = document.getElementById('comicModalOverlay');
        const closeBtn = document.getElementById('comicModalClose');
        if (closeBtn) closeBtn.addEventListener('click', closeComicModal);
        if (overlay) {
            overlay.addEventListener('click', function(evt) {
                if (evt.target === overlay) closeComicModal();
            });
        }
        
        // Fechar com ESC
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeComicModal();
        });
    }

    // Sistema de menu do usuário
    function setupUserMenu() {
        const userAvatar = document.getElementById('userAvatar');
        const userDropdown = document.getElementById('userDropdown');
        
        if (!userAvatar || !userDropdown) return;
        
        // Abrir/fechar menu ao clicar no avatar
        userAvatar.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });
        
        // Fechar menu ao clicar fora
        document.addEventListener('click', function(e) {
            if (!userAvatar.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
        });
        
        // Fechar menu ao pressionar ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && userDropdown.classList.contains('active')) {
                userDropdown.classList.remove('active');
            }
        });
    }

    // Função para seguir/deseguir usuário
    function toggleFollow(userId, button) {
        const isFollowing = button.classList.contains('following');
        
        fetch('follow_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `user_id=${userId}&action=${isFollowing ? 'unfollow' : 'follow'}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                if(isFollowing) {
                    button.classList.remove('following');
                    button.innerHTML = '<i class="fas fa-user-plus"></i> Seguir';
                } else {
                    button.classList.add('following');
                    button.innerHTML = '<i class="fas fa-user-check"></i> Seguindo';
                }
            } else {
                alert(data.message || 'Erro ao processar solicitação');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao processar solicitação');
        });
    }

    // Adicione esta função NOVA no seu JavaScript (pode colocar perto das outras funções de filtro)
function isDCClassic(comic) {
    const dcKeywords = ['batman', 'superman', 'mulher-maravilha', 'flash', 'liga da justiça', 
                       'arqueiro verde', 'aquaman', 'lanterna verde', 'dc', 'gotham', 'metropolis'];
    
    const title = comic.title.toLowerCase();
    const description = comic.description ? comic.description.toLowerCase() : '';
    
    return dcKeywords.some(keyword => 
        title.includes(keyword) || description.includes(keyword)
    ) && (comic.categories && comic.categories.includes('classicos'));
}

    // Inicialização completa
    async function initializePage() {
        setupTheme();
        setupCategoryFilter();
        setupModalSearch();
        setupCarouselNavigation();
        setupComicModalHandlers();
        setupUserMenu();
        
        // Inicializar sistema de progresso (agora assíncrono)
        await setupReadingProgress();
        
        console.log('Sistema de quadrinhos inicializado!');
    }

    // Inicializar a página quando o DOM estiver carregado
    document.addEventListener('DOMContentLoaded', function() {
        initializePage();
    });
</script>
</body>
</html>
