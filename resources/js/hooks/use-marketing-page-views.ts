import { reportPageView } from '@/lib/marketing';
import type { SiteSettings } from '@/types/site';
import { useEffect } from 'react';

/**
 * Counts the pages an Inertia visit walks through, for every tag configured
 * in the admin (Маркетинг). Does nothing until one is. A page view is never a
 * conversion; the lead is reported from the inquiry form.
 */
export function useMarketingPageViews(settings: SiteSettings, url: string): void {
    useEffect(() => {
        reportPageView(settings);
        // Only a new page is a new page view; settings ride along on every
        // response and must not count one by themselves.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [url]);
}
