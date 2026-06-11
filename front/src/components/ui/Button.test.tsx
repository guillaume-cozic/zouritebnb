import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { Button } from './Button';

test('renders a type=button by default and fires onClick', () => {
  const onClick = jest.fn();
  render(<Button onClick={onClick}>Save</Button>);

  const button = screen.getByRole('button', { name: 'Save' });
  expect(button).toHaveAttribute('type', 'button');

  fireEvent.click(button);
  expect(onClick).toHaveBeenCalledTimes(1);
});

test('is disabled and silent while loading', () => {
  const onClick = jest.fn();
  render(<Button loading onClick={onClick}>Save</Button>);

  const button = screen.getByRole('button', { name: 'Save' });
  expect(button).toBeDisabled();

  fireEvent.click(button);
  expect(onClick).not.toHaveBeenCalled();
});

test('applies the variant and size classes', () => {
  render(<Button variant="danger" size="sm">Delete</Button>);

  const button = screen.getByRole('button', { name: 'Delete' });
  expect(button.className).toContain('bg-danger-600');
  expect(button.className).toContain('h-9');
});
