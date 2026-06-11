import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { Modal } from './Modal';

test('renders nothing when closed', () => {
  render(
    <Modal open={false} onClose={jest.fn()} title="Hidden">
      content
    </Modal>
  );

  expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
});

test('renders a dialog with its title, body and footer when open', () => {
  render(
    <Modal open onClose={jest.fn()} title="Confirm" footer={<button>OK</button>}>
      Are you sure?
    </Modal>
  );

  const dialog = screen.getByRole('dialog');
  expect(dialog).toHaveAttribute('aria-modal', 'true');
  expect(screen.getByText('Confirm')).toBeInTheDocument();
  expect(screen.getByText('Are you sure?')).toBeInTheDocument();
  expect(screen.getByRole('button', { name: 'OK' })).toBeInTheDocument();
});

test('closes on Escape', () => {
  const onClose = jest.fn();
  render(
    <Modal open onClose={onClose} title="Confirm">
      content
    </Modal>
  );

  fireEvent.keyDown(document, { key: 'Escape' });
  expect(onClose).toHaveBeenCalledTimes(1);
});

test('closes on overlay click but not on panel click', () => {
  const onClose = jest.fn();
  render(
    <Modal open onClose={onClose} title="Confirm">
      content
    </Modal>
  );

  fireEvent.click(screen.getByText('content'));
  expect(onClose).not.toHaveBeenCalled();

  fireEvent.click(screen.getByRole('presentation'));
  expect(onClose).toHaveBeenCalledTimes(1);
});
