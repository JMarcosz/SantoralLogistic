import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <AppLogoIcon glow={true} />
            <div className="ml-3 grid flex-1 text-left">
                <span className="truncate text-base leading-tight font-bold tracking-tight">
                    Santoral Logistic
                </span>
                <span className="truncate text-xs font-medium text-muted-foreground">
                    Cima Alta
                </span>
            </div>
        </>
    );
}
