import icon128 from './icon-128.png';

interface AppLogoIconProps {
    glow?: boolean;
}

export default function AppLogoIcon({ glow }: AppLogoIconProps) {
    return (
        <div
            className={`gradient-primary shadow-premium-md ${glow ? '' : 'glow-primary'} flex h-10 w-10 items-center justify-center rounded-lg`}
            style={{
                background: 'linear-gradient(135deg, #351804 0%, #0f0000 100%)',
            }}
        >
            <img
                src={icon128}
                alt="Santoral Logo"
                className="h-8 w-8 object-contain"
            />
        </div>
    );
}

{
    /* <div
            className="flex aspect-square size-10 items-center justify-center rounded-xl bg-gradient-to-br shadow-md shadow-primary/15 transition-all group-hover:scale-105 group-hover:shadow-xl group-hover:shadow-primary/40"
            style={{
                background: 'linear-gradient(135deg, #351804 0%, #0f0000 100%)',
            }}
        ></div> */
}
