import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { TruckIcon } from './icons';

/** The file the site shipped with, used until a logo is uploaded in the admin. */
const FALLBACK_LOGO = '/assets/img/logo.webp';

interface Props {
    siteName?: string;
    tagline?: string;
    /** Uploaded under Настройки на сайта → Лого; empty keeps the shipped file. */
    logo?: string | null;
}

export default function Brand({ siteName = 'Глобал Карго Спед', tagline = 'Транспорт · Логистика', logo }: Props) {
    // The original markup removed the <img> on error and fell back to the
    // inline truck mark; this reproduces that without touching the DOM. The
    // failure is remembered per source, so replacing a broken logo in the admin
    // brings the image back instead of staying stuck on the truck.
    const [failedSrc, setFailedSrc] = useState<string | null>(null);

    const src = logo || FALLBACK_LOGO;

    return (
        <Link href="/" className="brand">
            <span className="logo" aria-hidden="true">
                {failedSrc !== src && (
                    <img className="logo-img" src={src} width={62} height={62} decoding="async" alt="" onError={() => setFailedSrc(src)} />
                )}
                <TruckIcon />
            </span>
            <span>
                {siteName}
                <small>{tagline}</small>
            </span>
        </Link>
    );
}
