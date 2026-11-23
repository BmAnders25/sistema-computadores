@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación">
        <ul>
            <li>
                @if ($paginator->onFirstPage())
                    <span class="is-disabled" aria-disabled="true">Anterior</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="src-pagination-dots">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="is-active" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
                @else
                    <span class="is-disabled" aria-disabled="true">Siguiente</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
