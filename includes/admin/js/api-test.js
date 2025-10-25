(function () {
    'use strict';

    if ( 'undefined' === typeof tmdbPluginApiTest ) {
        return;
    }

    const form = document.getElementById( 'tmdb-plugin-api-test-form' );
    const resultsContainer = document.getElementById( 'tmdb-plugin-api-test-results' );
    const statusContainer = document.getElementById( 'tmdb-plugin-api-test-status' );

    if ( ! form || ! resultsContainer || ! statusContainer ) {
        return;
    }

    const { ajaxUrl, nonce, strings, imageBaseUrl } = tmdbPluginApiTest;

    const renderStatus = ( message, type = '' ) => {
        statusContainer.textContent = message;
        statusContainer.className = 'tmdb-plugin-api-test__status';

        if ( type ) {
            statusContainer.classList.add( `tmdb-plugin-api-test__status--${ type }` );
        }
    };

    const hasApiKey = '1' === statusContainer.getAttribute( 'data-has-api-key' );

    if ( ! hasApiKey ) {
        renderStatus( strings.missingApiKey, 'warning' );
    }

    const clearResults = () => {
        resultsContainer.innerHTML = '';
    };

    const createResultItem = ( result ) => {
        const item = document.createElement( 'li' );
        item.className = 'tmdb-plugin-api-test__result';

        const title = document.createElement( 'h3' );
        title.className = 'tmdb-plugin-api-test__result-title';
        title.textContent = result.title || strings.unexpected;
        item.appendChild( title );

        const metaParts = [];

        if ( result.media_type ) {
            metaParts.push( result.media_type.toUpperCase() );
        }

        if ( result.release_date ) {
            metaParts.push( result.release_date );
        }

        if ( result.language ) {
            metaParts.push( result.language.toUpperCase() );
        }

        if ( metaParts.length ) {
            const meta = document.createElement( 'p' );
            meta.className = 'tmdb-plugin-api-test__result-meta';
            meta.textContent = metaParts.join( ' • ' );
            item.appendChild( meta );
        }

        if ( result.poster_path ) {
            const figure = document.createElement( 'figure' );
            figure.className = 'tmdb-plugin-api-test__result-figure';

            const img = document.createElement( 'img' );
            img.src = `${ imageBaseUrl }/${ result.poster_path }`;
            img.alt = '';
            img.loading = 'lazy';
            figure.appendChild( img );
            item.appendChild( figure );
        }

        if ( result.overview ) {
            const overview = document.createElement( 'p' );
            overview.className = 'tmdb-plugin-api-test__result-overview';
            overview.textContent = result.overview;
            item.appendChild( overview );
        }

        if ( 'number' === typeof result.vote_average && ! Number.isNaN( result.vote_average ) ) {
            const rating = document.createElement( 'p' );
            rating.className = 'tmdb-plugin-api-test__result-rating';
            rating.textContent = `⭐ ${ result.vote_average.toFixed( 1 ) }`;
            item.appendChild( rating );
        }

        return item;
    };

    const handleResponse = ( payload ) => {
        const { success } = payload;

        if ( ! success ) {
            clearResults();
            renderStatus( payload.data && payload.data.message ? payload.data.message : strings.unexpected, 'error' );
            return;
        }

        const data = payload.data || {};
        const { results = [], usedFallback = false, language = '' } = data;

        clearResults();

        if ( ! Array.isArray( results ) || ! results.length ) {
            renderStatus( strings.noResults, 'info' );
            return;
        }

        results.forEach( ( result ) => {
            resultsContainer.appendChild( createResultItem( result ) );
        } );

        if ( usedFallback ) {
            if ( language ) {
                renderStatus( strings.fallbackNoticeLanguage.replace( '%s', language ), 'warning' );
            } else {
                renderStatus( strings.fallbackNotice, 'warning' );
            }
        } else {
            renderStatus( '' );
        }
    };

    const performSearch = ( query ) => {
        const params = new URLSearchParams();
        params.append( 'action', 'tmdb_plugin_api_test_search' );
        params.append( 'nonce', nonce );
        params.append( 'query', query );

        renderStatus( strings.fetching, 'loading' );

        window.fetch( ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: params.toString(),
        } )
            .then( ( response ) => response.json() )
            .then( handleResponse )
            .catch( () => {
                renderStatus( strings.unexpected, 'error' );
                clearResults();
            } );
    };

    form.addEventListener( 'submit', ( event ) => {
        event.preventDefault();

        const queryField = form.querySelector( '#tmdb-plugin-api-test-query' );
        const value = queryField ? queryField.value.trim() : '';

        if ( ! value ) {
            renderStatus( strings.missingQuery, 'error' );
            clearResults();
            return;
        }

        if ( ! hasApiKey ) {
            renderStatus( strings.missingApiKey, 'error' );
            clearResults();
            return;
        }

        performSearch( value );
    } );
}());
