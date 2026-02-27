import * as React from "react"
import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"

import { cn } from "@/lib/utils"

const badgeVariants = cva(
  "inline-flex items-center justify-center rounded-md border px-2.5 py-0.5 text-xs font-semibold w-fit whitespace-nowrap shrink-0 [\u0026>svg]:size-3 gap-1 [\u0026>svg]:pointer-events-none transition-all",
  {
    variants: {
      variant: {
        default:
          "border-transparent bg-primary text-primary-foreground shadow-premium-xs [a\u0026]:hover:bg-primary/90 [a\u0026]:hover:shadow-premium-sm",
        secondary:
          "border-transparent bg-secondary text-secondary-foreground shadow-premium-xs [a\u0026]:hover:bg-secondary/90 [a\u0026]:hover:shadow-premium-sm",
        destructive:
          "border-transparent bg-destructive text-destructive-foreground shadow-premium-xs [a\u0026]:hover:bg-destructive/90 [a\u0026]:hover:shadow-premium-sm",
        outline:
          "text-foreground border-border [a\u0026]:hover:bg-accent [a\u0026]:hover:text-accent-foreground",
        success:
          "border-transparent bg-success text-success-foreground shadow-premium-xs [a\u0026]:hover:brightness-110",
        warning:
          "border-transparent bg-warning text-warning-foreground shadow-premium-xs [a\u0026]:hover:brightness-110",
        info:
          "border-transparent bg-info text-info-foreground shadow-premium-xs [a\u0026]:hover:brightness-110",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
)

function Badge({
  className,
  variant,
  asChild = false,
  ...props
}: React.ComponentProps<"span"> &
  VariantProps<typeof badgeVariants> & { asChild?: boolean }) {
  const Comp = asChild ? Slot : "span"

  return (
    <Comp
      data-slot="badge"
      className={cn(badgeVariants({ variant }), className)}
      {...props}
    />
  )
}

export { Badge, badgeVariants }
