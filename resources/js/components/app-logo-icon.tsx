import type { SVGAttributes } from 'react';

type AppLogoIconProps = SVGAttributes<SVGElement> & {
    primaryColor?: string; // top face
    leftColor?: string; // left face
    rightColor?: string; // right face
};

export default function AppLogoIcon({
    primaryColor,
    leftColor,
    rightColor,
    ...props
}: AppLogoIconProps) {
    const top = primaryColor ?? 'var(--primary)';
    const left = leftColor ?? 'var(--foreground)';
    const right = rightColor ?? 'var(--background)';

    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 320 320"
            role="img"
            aria-label="Stone Logistic Platform Cube Logo"
            {...props}
        >
            <g transform="translate(30,20)">
                {/* Top face */}
                <polygon points="130,0 260,70 130,140 0,70" fill={top} />

                {/* Left face */}
                <polygon points="0,70 130,140 130,280 0,210" fill={left} />

                {/* Right face */}
                <polygon points="260,70 130,140 130,280 260,210" fill={right} />
            </g>
        </svg>
    );
}
