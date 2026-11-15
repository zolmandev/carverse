import { SVGAttributes, useId } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGSVGElement>) {
    const clipId = `rounded-app-mark-${useId().replaceAll(':', '')}`;

    return (
        <svg viewBox="0 0 701 701" role="img" aria-label="Carverse KE" {...props}>
            <defs>
                <clipPath id={clipId}>
                    <rect width="701" height="701" rx="88" />
                </clipPath>
            </defs>
            <image
                href="/carverse-mark.jpg"
                width="701"
                height="701"
                preserveAspectRatio="xMidYMid meet"
                clipPath={`url(#${clipId})`}
            />
        </svg>
    );
}
