import type { Partner } from '@/types/site';

/**
 * Each logo sits in its own `.plogo.glass` tile — the original built these with
 * createElement, and `.plogo` is the tile, not the image.
 */
export default function PartnerGrid({ partners }: { partners: Partner[] }) {
    if (partners.length === 0) {
        return null;
    }

    return (
        <div className="partner-grid reveal">
            {partners.map((partner) => (
                <div className="plogo glass" key={partner.name}>
                    {partner.logo ? (
                        <img src={partner.logo} alt={partner.name || 'Партньор'} loading="lazy" decoding="async" />
                    ) : (
                        <span>{partner.name}</span>
                    )}
                </div>
            ))}
        </div>
    );
}
