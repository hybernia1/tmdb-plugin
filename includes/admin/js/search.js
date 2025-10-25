(() => {
    const config = window.tmdbPluginSearch;

    if ( ! config ) {
        return;
    }

    const form = document.getElementById( 'tmdb-plugin-search-form' );
    const queryInput = document.getElementById( 'tmdb-plugin-search-query' );
    const statusEl = document.getElementById( 'tmdb-plugin-search-status' );
    const resultsEl = document.getElementById( 'tmdb-plugin-search-results' );
    const paginationEl = document.getElementById( 'tmdb-plugin-search-pagination' );

    if ( ! form || ! queryInput || ! statusEl || ! resultsEl || ! paginationEl ) {
        return;
    }

    let currentQuery = '';
    let currentPage = 1;
    let totalPages = 0;
    let loading = false;

    const { strings, ajaxUrl, nonce, imageBaseUrl, hasApiKey } = config;

    const setStatus = ( message = '', type = '' ) => {
        statusEl.textContent = message;
        statusEl.className = 'tmdb-plugin-search__status';

        if ( type ) {
            statusEl.classList.add( `tmdb-plugin-search__status--${ type }` );
        }
    };

    const resetResults = () => {
        resultsEl.innerHTML = '';
        paginationEl.innerHTML = '';
    };

    const buildResultItem = ( movie ) => {
        const item = document.createElement( 'li' );
        item.className = 'tmdb-plugin-search__result';

        if ( movie.poster_path ) {
            const figure = document.createElement( 'figure' );
            figure.className = 'tmdb-plugin-search__poster';

            const img = document.createElement( 'img' );
            img.loading = 'lazy';
            img.src = `${ imageBaseUrl.replace( /\/$/, '' ) }/${ movie.poster_path.replace( /^\//, '' ) }`;
            img.alt = strings.posterAlt.replace( '%s', movie.title || movie.original_title || '' );

            figure.appendChild( img );
            item.appendChild( figure );
        }

        const body = document.createElement( 'div' );
        body.className = 'tmdb-plugin-search__body';

        if ( movie.title ) {
            const titleEl = document.createElement( 'h3' );
            titleEl.className = 'tmdb-plugin-search__title';
            titleEl.textContent = movie.title;
            body.appendChild( titleEl );
        }

        const metaParts = [];

        if ( movie.release_date ) {
            metaParts.push( movie.release_date );
        }

        if ( movie.language ) {
            metaParts.push( movie.language.toUpperCase() );
        }

        if ( typeof movie.vote_average === 'number' && movie.vote_average > 0 ) {
            metaParts.push( `⭐ ${ movie.vote_average.toFixed( 1 ) }` );
        }

        if ( typeof movie.vote_count === 'number' && movie.vote_count > 0 ) {
            metaParts.push( `${ movie.vote_count.toLocaleString() } ${ strings.votesLabel || 'votes' }` );
        }

        if ( metaParts.length > 0 ) {
            const metaEl = document.createElement( 'p' );
            metaEl.className = 'tmdb-plugin-search__meta';
            metaEl.textContent = metaParts.join( ' • ' );
            body.appendChild( metaEl );
        }

        if ( movie.overview ) {
            const overviewEl = document.createElement( 'p' );
            overviewEl.className = 'tmdb-plugin-search__overview';
            overviewEl.textContent = movie.overview;
            body.appendChild( overviewEl );
        }

        const actions = document.createElement( 'div' );
        actions.className = 'tmdb-plugin-search__actions';

        const importButton = document.createElement( 'button' );
        importButton.type = 'button';
        importButton.className = 'button button-secondary tmdb-plugin-search__import';
        importButton.dataset.movieId = String( movie.id );
        importButton.textContent = strings.import;

        actions.appendChild( importButton );
        body.appendChild( actions );
        item.appendChild( body );

        return item;
    };

    const renderResults = ( results ) => {
        resetResults();

        if ( ! Array.isArray( results ) || results.length === 0 ) {
            return;
        }

        const fragment = document.createDocumentFragment();

        results.forEach( ( movie ) => {
            fragment.appendChild( buildResultItem( movie ) );
        } );

        resultsEl.appendChild( fragment );
    };

    const renderPagination = () => {
        paginationEl.innerHTML = '';

        if ( totalPages <= 1 ) {
            return;
        }

        const list = document.createElement( 'ul' );
        list.className = 'tmdb-plugin-search__pagination-list';

        const prevItem = document.createElement( 'li' );
        const prevButton = document.createElement( 'button' );
        prevButton.type = 'button';
        prevButton.className = 'button tmdb-plugin-search__page-btn';
        prevButton.textContent = strings.paginationPrevious;
        prevButton.disabled = currentPage <= 1;
        prevButton.addEventListener( 'click', () => fetchResults( Math.max( 1, currentPage - 1 ) ) );
        prevItem.appendChild( prevButton );
        list.appendChild( prevItem );

        const infoItem = document.createElement( 'li' );
        infoItem.className = 'tmdb-plugin-search__page-info';
        infoItem.textContent = `${ currentPage } / ${ totalPages }`;
        list.appendChild( infoItem );

        const nextItem = document.createElement( 'li' );
        const nextButton = document.createElement( 'button' );
        nextButton.type = 'button';
        nextButton.className = 'button tmdb-plugin-search__page-btn';
        nextButton.textContent = strings.paginationNext;
        nextButton.disabled = currentPage >= totalPages;
        nextButton.addEventListener( 'click', () => fetchResults( Math.min( totalPages, currentPage + 1 ) ) );
        nextItem.appendChild( nextButton );
        list.appendChild( nextItem );

        paginationEl.appendChild( list );
    };

    const fetchResults = async ( page ) => {
        if ( loading ) {
            return;
        }

        if ( ! hasApiKey ) {
            setStatus( strings.missingApiKey, 'error' );
            return;
        }

        loading = true;
        setStatus( strings.searching, 'info' );
        resultsEl.classList.add( 'is-loading' );

        try {
            const params = new URLSearchParams();
            params.append( 'action', 'tmdb_plugin_search_movie' );
            params.append( 'nonce', nonce );
            params.append( 'query', currentQuery );
            params.append( 'page', String( page ) );

            const response = await fetch( ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                },
                body: params.toString(),
                credentials: 'same-origin',
            } );

            if ( ! response.ok ) {
                throw new Error( strings.unexpected );
            }

            const payload = await response.json();

            if ( ! payload.success ) {
                throw new Error( payload.data && payload.data.message ? payload.data.message : strings.unexpected );
            }

            const data = payload.data;

            currentPage = Number( data.page ) || page;
            totalPages = Number( data.totalPages ) || 1;

            renderResults( data.results );
            renderPagination();

            if ( Array.isArray( data.results ) && data.results.length === 0 ) {
                setStatus( strings.noResults, 'info' );
            } else if ( data.usedFallback ) {
                setStatus( strings.fallbackNotice, 'warning' );
            } else {
                setStatus();
            }
        } catch ( error ) {
            resetResults();
            setStatus( error.message || strings.unexpected, 'error' );
        } finally {
            resultsEl.classList.remove( 'is-loading' );
            loading = false;
        }
    };

    const importMovie = async ( movieId, button ) => {
        if ( ! movieId ) {
            return;
        }

        button.disabled = true;
        button.classList.add( 'is-busy' );
        const originalLabel = button.textContent;
        button.textContent = strings.importing;

        try {
            const params = new URLSearchParams();
            params.append( 'action', 'tmdb_plugin_import_movie' );
            params.append( 'nonce', nonce );
            params.append( 'movieId', movieId );

            const response = await fetch( ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                },
                body: params.toString(),
                credentials: 'same-origin',
            } );

            const json = await response.json();

            if ( ! response.ok || ! json.success ) {
                throw new Error( ( json.data && json.data.message ) || strings.importError );
            }

            button.classList.remove( 'is-busy' );
            button.textContent = strings.importSuccess;
            button.classList.add( 'is-success' );
            setStatus( json.data && json.data.message ? json.data.message : strings.importSuccess, 'success' );
        } catch ( error ) {
            button.disabled = false;
            button.classList.remove( 'is-busy' );
            button.textContent = originalLabel;
            setStatus( error.message || strings.importError, 'error' );
        }
    };

    form.addEventListener( 'submit', ( event ) => {
        event.preventDefault();

        const value = queryInput.value.trim();

        if ( ! value ) {
            setStatus( strings.missingQuery, 'error' );
            return;
        }

        currentQuery = value;
        currentPage = 1;
        totalPages = 0;

        fetchResults( 1 );
    } );

    resultsEl.addEventListener( 'click', ( event ) => {
        const target = event.target;

        if ( ! target || ! target.classList.contains( 'tmdb-plugin-search__import' ) ) {
            return;
        }

        const movieId = target.dataset.movieId;

        if ( ! movieId ) {
            return;
        }

        importMovie( movieId, target );
    } );
})();
