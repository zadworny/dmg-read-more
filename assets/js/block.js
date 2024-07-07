const { registerBlockType } = wp.blocks;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, TextControl, Button, SelectControl } = wp.components;
const { useState, useEffect, useRef, useCallback, useMemo } = wp.element;
const { apiFetch } = wp;

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}

registerBlockType('dmg/read-more', {
    title: 'Read More',
    icon: 'admin-links',
    category: 'widgets',
    attributes: {
        postId: { type: 'number', default: 0 },
        postTitle: { type: 'string', default: '' },
        postUrl: { type: 'string', default: '' },
    },
    edit: function ({ attributes, setAttributes }) {
        const [pagination, setPagination] = useState({
            posts: [],
            page: 1,
            totalPages: 1,
            totalResults: 0,
            loading: false,
            loadingText: 'Loading.',
            search: ''
        });

        const { posts, page, totalPages, totalResults, loading, loadingText, search } = pagination;

        const updatePagination = (updates) => {
            setPagination(prev => ({ ...prev, ...updates }));
        };

        const abortControllerRef = useRef();

        useEffect(() => {
            let loadingInterval;
            if (loading) {
                loadingInterval = setInterval(() => {
                    setPagination(prev => ({
                        ...prev,
                        loadingText: prev.loadingText === 'Loading...' ? 'Loading.' : prev.loadingText + '.'
                    }));
                }, 500);
            } else {
                updatePagination({ loadingText: 'Loading.' });
                clearInterval(loadingInterval);
            }
            return () => clearInterval(loadingInterval);
        }, [loading]);

        const fetchPosts = useCallback(async (search, page) => {
            updatePagination({ loading: true });
            if (abortControllerRef.current) {
                abortControllerRef.current.abort();
            }
            abortControllerRef.current = new AbortController();
            const signal = abortControllerRef.current.signal;

            let path = `/wp/v2/posts?per_page=10&page=${page}&_fields=id,title,link&orderby=date&order=desc&status=publish`;

            if (search) {
                if (search.startsWith('ID:')) {
                    const idQuery = search.replace('ID:', '').trim();
                    path = `/wp/v2/posts?include[]=${idQuery}&_fields=id,title,link&status=publish`;
                } else {
                    path += `&search=${encodeURIComponent(search)}`;
                }
            }

            try {
                const response = await apiFetch({ path, signal, parse: false });
                if (!response.ok) {
                    throw new Error('Failed to fetch posts');
                }
                const data = await response.json();
                const totalPages = parseInt(response.headers.get('X-WP-TotalPages'), 10) || 1;
                const totalResults = parseInt(response.headers.get('X-WP-Total'), 10) || 0;

                updatePagination({
                    posts: data,
                    totalPages,
                    totalResults,
                    loading: false
                });
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Fetch error:', error);
                    updatePagination({ loading: false, error: 'Failed to fetch posts. Please try again later.' });
                }
            }
        }, []);

        const debouncedFetchPosts = useCallback(debounce(fetchPosts, 300), [fetchPosts]);

        useEffect(() => {
            debouncedFetchPosts(search, page);
            return () => {
                if (abortControllerRef.current) {
                    abortControllerRef.current.abort();
                }
            };
        }, [debouncedFetchPosts, search, page]);

        const goToPage = (newPage) => {
            if (newPage > 0 && newPage <= totalPages) {
                updatePagination({ page: newPage });
            }
        };

        const handleSearchChange = (value) => {
            updatePagination({ search: value, page: 1 });
        };

        const renderPageOptions = useMemo(() => {
            const options = [];
            if (totalPages === 0) {
                options.push({ label: `Page 0`, value: 0 });
            }
            for (let i = 1; i <= totalPages; i++) {
                options.push({ label: `Page ${i}`, value: i });
            }
            return options;
        }, [totalPages]);

        return wp.element.createElement('div', null,
            wp.element.createElement(InspectorControls, null,
                wp.element.createElement(PanelBody, { title: "Post Selection" },
                    wp.element.createElement('div', { className: 'dmg-search-box', style: { display: 'flex', alignItems: 'center' } },
                        wp.element.createElement(TextControl, {
                            label: "Search Posts by Title or ID",
                            placeholder: "Start with 'ID:' to search by post ID",
                            value: search,
                            onChange: handleSearchChange,
                            style: { flex: 1 }
                        })
                    ),
                    wp.element.createElement('div', { className: 'dmg-search-box', style: { display: 'flex', justifyContent: 'space-between' } },
                        wp.element.createElement(Button, {
                            isSecondary: true,
                            onClick: () => goToPage(page - 1),
                            disabled: page <= 1
                        }, 'Prev'),
                        wp.element.createElement(SelectControl, {
                            value: page,
                            options: renderPageOptions,
                            onChange: (value) => goToPage(parseInt(value)),
                        }),
                        wp.element.createElement(Button, {
                            isSecondary: true,
                            onClick: () => goToPage(page + 1),
                            disabled: page >= totalPages
                        }, 'Next')
                    ),
                    loading ? wp.element.createElement('p', { className: 'dmg-info-text' }, loadingText) :
                    wp.element.createElement('p', { className: 'dmg-info-text' },
                        wp.element.createElement('span', null, totalResults.toLocaleString('en-US')),
                        ' results ',
                        wp.element.createElement('span', null, totalPages.toLocaleString('en-US')),
                        ' pages'
                    ),
                    wp.element.createElement('div', null,
                        loading ? wp.element.createElement('p', null, '') :
                        posts.length ? posts.map(post => (
                            wp.element.createElement('div', { key: post.id, className: 'post-item', style: { marginBottom: '8px' } },
                                wp.element.createElement(Button, {
                                    isSecondary: true,
                                    onClick: () => setAttributes({
                                        postId: post.id,
                                        postTitle: post.title.rendered,
                                        postUrl: post.link
                                    }),
                                    className: 'post-title-button'
                                }, `${post.title.rendered} (ID:${post.id})`)
                            )
                        )) : wp.element.createElement('p', null, 'No posts found.')
                    )
                )
            ),
            wp.element.createElement('p', { className: "dmg-read-more" },
                `Read More: `,
                wp.element.createElement('a', { href: attributes.postUrl }, attributes.postTitle)
            )
        );
    },
    save: function ({ attributes }) {
        return wp.element.createElement('p', { className: "dmg-read-more" },
            `Read More: `,
            wp.element.createElement('a', { href: attributes.postUrl }, attributes.postTitle)
        );
    }
});
