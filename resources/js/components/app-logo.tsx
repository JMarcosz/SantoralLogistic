import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-10 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-primary/80 shadow-lg shadow-primary/30 transition-all group-hover:scale-105 group-hover:shadow-xl group-hover:shadow-primary/40">
                <AppLogoIcon className="size-6 fill-current text-primary-foreground" />
            </div>
            <div className="ml-3 grid flex-1 text-left">
                <span className="truncate text-base leading-tight font-bold tracking-tight">
                    Stone Logistic
                </span>
                <span className="truncate text-xs font-medium text-muted-foreground">
                    Platform
                </span>
            </div>
        </>
    );
}
