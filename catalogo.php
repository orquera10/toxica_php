<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

function escapar(?string $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function precio_argentino($valor): string
{
    $numero = (float) $valor;
    $decimales = floor($numero) === $numero ? 0 : 2;
    return '$ ' . number_format($numero, $decimales, ',', '.');
}

$categoriasInfo = [
    'BEBIDA' => ['nombre' => 'Sin alcohol', 'icono' => '🥤'],
    'BEBIDA ALCOHOLICA' => ['nombre' => 'Cervezas', 'icono' => '🍺'],
    'COMIDA' => ['nombre' => 'Comidas', 'icono' => '🍕'],
];

$productos = [];
$consulta = mysqli_query(
    $con,
    "SELECT _id, NOMBRE, PRECIO, STOCK, TIPO, URL_IMG
     FROM producto
     WHERE VISIBLE = 1 AND EN_CATALOGO = 1 AND STOCK > 0
     ORDER BY FIELD(TIPO, 'BEBIDA', 'BEBIDA ALCOHOLICA', 'COMIDA'), IMPORTANCIA, NOMBRE"
);

if ($consulta) {
    while ($fila = mysqli_fetch_assoc($consulta)) {
        $productos[] = $fila;
    }
}

$productosPorCategoria = [];
foreach ($productos as $producto) {
    $tipo = trim((string) $producto['TIPO']);
    $productosPorCategoria[$tipo][] = $producto;
}

$totalProductos = count($productos);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#121711">
    <meta name="description" content="Catálogo de productos y precios disponibles en La Tóxica.">
    <title>La Tóxica | Menú</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/catalogo.css?v=1">
</head>
<body>
    <header class="hero">
        <div class="hero__glow" aria-hidden="true"></div>
        <div class="hero__content">
            <img class="hero__logo" src="img/logoToxica2.png" alt="La Tóxica">
            <div class="hero__copy">
                <span class="hero__eyebrow">LA TÓXICA FÚTBOL</span>
                <h1>Algo rico<br><span>para el tercer tiempo.</span></h1>
                <p>Elegí lo que quieras y pedilo en la barra.</p>
            </div>
        </div>
        <div class="hero__status">
            <span><i aria-hidden="true"></i> Menú disponible</span>
            <strong><?= $totalProductos ?> productos</strong>
        </div>
    </header>

    <main>
        <div class="controls-wrap">
            <div class="controls">
                <label class="search" for="buscar-producto">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                    <input id="buscar-producto" type="search" placeholder="Buscar una bebida o comida..." autocomplete="off">
                </label>

                <nav class="category-tabs" aria-label="Categorías">
                    <button class="category-tab is-active" type="button" data-category="todos" aria-pressed="true"><span>✦</span> Todo</button>
                    <?php foreach ($productosPorCategoria as $tipo => $items): ?>
                        <?php $info = $categoriasInfo[$tipo] ?? ['nombre' => ucfirst(mb_strtolower($tipo)), 'icono' => '•']; ?>
                        <button class="category-tab" type="button" data-category="<?= escapar($tipo) ?>" aria-pressed="false">
                            <span><?= $info['icono'] ?></span> <?= escapar($info['nombre']) ?>
                        </button>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>

        <div class="catalog" id="catalogo">
            <?php if (!$productos): ?>
                <section class="no-products">
                    <span>⚽</span>
                    <h2>Estamos actualizando el menú</h2>
                    <p>Consultá en la barra por los productos disponibles.</p>
                </section>
            <?php endif; ?>

            <?php foreach ($productosPorCategoria as $tipo => $items): ?>
                <?php $info = $categoriasInfo[$tipo] ?? ['nombre' => ucfirst(mb_strtolower($tipo)), 'icono' => '•']; ?>
                <section class="category-section" data-section-category="<?= escapar($tipo) ?>">
                    <div class="section-title">
                        <div><span class="section-title__icon"><?= $info['icono'] ?></span><h2><?= escapar($info['nombre']) ?></h2></div>
                        <span><?= count($items) ?> opciones</span>
                    </div>

                    <div class="product-grid">
                        <?php foreach ($items as $producto): ?>
                            <article class="product-card" data-product data-category="<?= escapar($tipo) ?>" data-name="<?= escapar(mb_strtolower((string) $producto['NOMBRE'])) ?>">
                                <div class="product-card__image-wrap">
                                    <img class="product-card__image" src="<?= escapar($producto['URL_IMG']) ?>" alt="<?= escapar($producto['NOMBRE']) ?>" loading="lazy" onerror="this.onerror=null;this.src='img/productos/imagen_articulo_por_defecto.jpg';">
                                </div>
                                <div class="product-card__body">
                                    <h3><?= escapar($producto['NOMBRE']) ?></h3>
                                    <div class="product-card__footer">
                                        <span class="available"><i aria-hidden="true"></i> Disponible</span>
                                        <strong><?= escapar(precio_argentino($producto['PRECIO'])) ?></strong>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <section class="empty-search" id="sin-resultados" hidden>
                <span>🔎</span><h2>No encontramos ese producto</h2>
                <p>Probá con otro nombre o mirá todas las categorías.</p>
                <button type="button" id="limpiar-busqueda">Ver todo el menú</button>
            </section>
        </div>
    </main>

    <footer>
        <img src="img/logoToxica2.png" alt="" aria-hidden="true">
        <div><strong>LA TÓXICA FÚTBOL</strong><span>Precios sujetos a actualización.</span></div>
    </footer>

    <script>
        (() => {
            const search = document.getElementById('buscar-producto');
            const tabs = [...document.querySelectorAll('.category-tab')];
            const cards = [...document.querySelectorAll('[data-product]')];
            const sections = [...document.querySelectorAll('[data-section-category]')];
            const empty = document.getElementById('sin-resultados');
            const clearButton = document.getElementById('limpiar-busqueda');
            let activeCategory = 'todos';
            const normalize = (text) => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();

            function filterCatalog() {
                const term = normalize(search.value);
                let visibleProducts = 0;
                cards.forEach((card) => {
                    const visible = (activeCategory === 'todos' || card.dataset.category === activeCategory) && normalize(card.dataset.name).includes(term);
                    card.hidden = !visible;
                    if (visible) visibleProducts++;
                });
                sections.forEach((section) => { section.hidden = !section.querySelector('[data-product]:not([hidden])'); });
                empty.hidden = visibleProducts !== 0 || cards.length === 0;
            }

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    activeCategory = tab.dataset.category;
                    tabs.forEach((item) => {
                        const selected = item === tab;
                        item.classList.toggle('is-active', selected);
                        item.setAttribute('aria-pressed', String(selected));
                    });
                    filterCatalog();
                    document.getElementById('catalogo').scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });
            search.addEventListener('input', filterCatalog);
            clearButton?.addEventListener('click', () => { search.value = ''; tabs[0]?.click(); search.focus(); });
        })();
    </script>
</body>
</html>
