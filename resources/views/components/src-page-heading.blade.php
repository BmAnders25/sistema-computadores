@props(['section', 'page'])

<div class="src-page-heading">
    <nav aria-label="Migas de pan">
        <ol>
            <li>S.R.C</li>
            <li>{{ $section }}</li>
            <li aria-current="page">{{ $page }}</li>
        </ol>
    </nav>
    <h1>{{ $slot }}</h1>
</div>
