export function useFormatting() {
    const formatDate = (dateString: string): string => {
        return new Date(dateString).toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const formatLongDate = (dateString: string): string => {
        return new Date(dateString).toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const formatSimpleDate = (dateString: string): string => {
        return new Date(dateString).toLocaleDateString();
    };

    const decodePaginationLabel = (label: string): string => {
        return label
            .replace(/&laquo;/g, '\u00AB')
            .replace(/&raquo;/g, '\u00BB')
            .replace(/&amp;/g, '&');
    };

    return {
        formatDate,
        formatLongDate,
        formatSimpleDate,
        decodePaginationLabel,
    };
}
