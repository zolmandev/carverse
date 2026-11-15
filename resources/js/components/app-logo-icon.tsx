import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGSVGElement>) {
    return (
        <svg viewBox="0 0 701 701" role="img" aria-label="Carverse KE" {...props}>
            <image href="/carverse-mark.jpg" width="701" height="701" preserveAspectRatio="xMidYMid meet" />
        </svg>
    );
}
