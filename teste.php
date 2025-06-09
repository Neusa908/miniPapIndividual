<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O que nossos usuários dizem?</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
    * {
        box-sizing: border-box;
        font-family: 'Montserrat', sans-serif;
        margin: 0;
        padding: 0;
    }

    body {
        background-color: #f2f2f2;
    }

    .section-comentarios {
        background-color: #ffd500;
        padding: 60px 20px 100px;
        text-align: center;
    }

    .section-comentarios h1 {
        font-size: 2.5em;
        margin-bottom: 40px;
        font-weight: 700;
    }

    .comentarios-container {
        display: flex;
        justify-content: center;
        gap: 40px;
        flex-wrap: wrap;
        max-width: 1200px;
        margin: 0 auto;
        transform: translateY(50px);
    }

    .comentario {
        background-color: #fff;
        border-radius: 16px;
        padding: 30px 20px;
        width: 280px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .comentario img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 20px;
    }

    .comentario p {
        color: #666;
        font-size: 0.95em;
        margin-bottom: 20px;
    }

    .nome {
        font-weight: 700;
        color: #333;
    }

    .cargo {
        color: #666;
        font-size: 0.9em;
        margin-top: 4px;
    }

    .creditos {
        margin-top: 40px;
        font-size: 0.8em;
        color: #555;
    }

    .creditos a {
        font-weight: 700;
        color: #000;
        text-decoration: none;
    }
    </style>
</head>

<body>

    <section class="section-comentarios">
        <h1>O que nossos usuários dizem?</h1>
        <div class="comentarios-container">
            <div class="comentario">
                <img src="https://img.freepik.com/free-photo/portrait-smiling-african-woman_23-2148824308.jpg"
                    alt="Celia Almeda">
                <p>Sample text. Click to select the text box. Click again or double click to start editing the text.</p>
                <div class="nome">Celia almeda</div>
                <div class="cargo">secretário</div>
            </div>
            <div class="comentario">
                <img src="https://img.freepik.com/free-photo/portrait-happy-handsome-man-wearing-glasses_23-2148824258.jpg"
                    alt="Nat Reynolds">
                <p>Sample text. Click to select the text box. Click again or double click to start editing the text.</p>
                <div class="nome">Nat Reynolds</div>
                <div class="cargo">Contador chefe</div>
            </div>
            <div class="comentario">
                <img src="https://img.freepik.com/free-photo/happy-young-man-smiling_23-2148824324.jpg"
                    alt="Bob Roberts">
                <p>Sample text. Click to select the text box. Click again or double click to start editing the text.</p>
                <div class="nome">Bob Roberts</div>
                <div class="cargo">Gerente de vendas</div>
            </div>
        </div>
        <div class="creditos">Imagens de <a href="https://www.freepik.com/" target="_blank">Freepik</a></div>
    </section>

</body>

</html>