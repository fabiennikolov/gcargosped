import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { TruckIcon } from './icons';

interface Props {
    siteName?: string;
    tagline?: string;
}

export default function Brand({ siteName = 'Глобал Карго Спед', tagline = 'Транспорт · Логистика' }: Props) {
    // The original markup removed the <img> on error and fell back to the
    // inline truck mark; this reproduces that without touching the DOM.
    const [logoFailed, setLogoFailed] = useState(false);

    return (
        <Link href="/" className="brand">
            <span className="logo" aria-hidden="true">
                {!logoFailed && (
                    <img
                        className="logo-img"
                        src="/assets/img/logo.webp"
                        width={62}
                        height={62}
                        decoding="async"
                        alt=""
                        onError={() => setLogoFailed(true)}
                    />
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
