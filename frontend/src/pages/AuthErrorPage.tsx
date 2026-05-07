import { Link, useSearchParams } from 'react-router-dom'

const MESSAGES: Record<string, string> = {
  invalid_domain: 'Só é permitido login com o e-mail corporativo autorizado.',
  auth_failed: 'Não foi possível concluir o login com o Google. Tente novamente.',
}

export default function AuthErrorPage() {
  const [params] = useSearchParams()
  const reason = params.get('reason') ?? ''
  const msg = MESSAGES[reason] ?? 'Ocorreu um erro ao entrar.'

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 p-6">
      <div className="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
        <h1 className="text-lg font-semibold text-gray-900">Não foi possível entrar</h1>
        <p className="mt-3 text-sm text-gray-600">{msg}</p>
        <Link
          to="/auth/login"
          className="mt-6 inline-block rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary/90"
        >
          Voltar ao login
        </Link>
      </div>
    </div>
  )
}
