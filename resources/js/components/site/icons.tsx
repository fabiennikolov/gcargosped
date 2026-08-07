/**
 * The inline SVGs the original static site used, lifted verbatim so stroke
 * weights and shapes stay identical. They inherit colour via currentColor.
 */
import type { SVGProps } from 'react';

const stroke: SVGProps<SVGSVGElement> = {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: 2,
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
};

export function TruckIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M1 3h15v13H1z" />
            <path d="M16 8h4l3 3v5h-7z" />
            <circle cx="5.5" cy="18.5" r="2.5" fill="#fff" stroke="none" />
            <circle cx="18.5" cy="18.5" r="2.5" fill="#fff" stroke="none" />
        </svg>
    );
}

export function PhoneIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg {...stroke} {...props}>
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z" />
        </svg>
    );
}

export function MailIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg {...stroke} {...props}>
            <path d="M4 4h16v16H4z" />
            <path d="M22 6l-10 7L2 6" />
        </svg>
    );
}

export function PinIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg {...stroke} {...props}>
            <path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 0 1 18 0z" />
            <circle cx="12" cy="10" r="3" />
        </svg>
    );
}

export function ClockIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg {...stroke} {...props}>
            <circle cx="12" cy="12" r="10" />
            <path d="M12 6v6l4 2" />
        </svg>
    );
}

export function BurgerIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" {...props}>
            <path d="M3 6h18M3 12h18M3 18h18" />
        </svg>
    );
}

export function CloseIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" {...props}>
            <path d="M18 6L6 18M6 6l12 12" />
        </svg>
    );
}

export function ArrowIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg {...stroke} {...props}>
            <path d="M5 12h14M13 6l6 6-6 6" />
        </svg>
    );
}

export function CheckIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg {...stroke} {...props}>
            <path d="M20 6L9 17l-5-5" />
        </svg>
    );
}

export function FacebookIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" {...props}>
            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
        </svg>
    );
}

export function InstagramIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} {...props}>
            <rect x="2" y="2" width="20" height="20" rx="5" />
            <circle cx="12" cy="12" r="4" />
            <circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" stroke="none" />
        </svg>
    );
}

export function LinkedInIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" {...props}>
            <path d="M4.98 3.5A2.5 2.5 0 1 1 0 3.5a2.5 2.5 0 0 1 4.98 0zM0 8h5v16H0zM8 8h4.8v2.2h.07c.67-1.2 2.3-2.5 4.73-2.5C22.4 7.7 24 10 24 14v10h-5v-8.6c0-2-.7-3.4-2.5-3.4-1.36 0-2.17.9-2.53 1.8-.13.32-.17.76-.17 1.2V24H8z" />
        </svg>
    );
}
