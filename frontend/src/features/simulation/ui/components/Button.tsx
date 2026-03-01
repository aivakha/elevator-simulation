import type { ButtonHTMLAttributes } from 'react';

type ButtonVariant = 'primary' | 'neutral' | 'danger' | 'warning';
type ButtonSize = 'md' | 'sm';

const variantClassNames: Record<ButtonVariant, string> = {
  primary: 'btn--primary',
  neutral: 'btn--neutral',
  danger: 'btn--danger',
  warning: 'btn--warning',
};

const sizeClassNames: Record<ButtonSize, string> = {
  md: 'btn--md',
  sm: 'btn--sm',
};

export function Button({
  variant = 'neutral',
  size = 'md',
  className = '',
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: ButtonVariant;
  size?: ButtonSize;
}) {
  return <button {...props} className={`btn ${variantClassNames[variant]} ${sizeClassNames[size]} ${className}`} />;
}
