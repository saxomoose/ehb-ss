<form method="POST" action={{ route('pin.activate', ['user' => $userId]) }}>
    @csrf
    <label>Please activate your account within 5 minutes of receiving this email.
        <input type="hidden" name="pin_code" value="{{ $pinCode }}">
    </label>
    <button type="submit">Activate your account</button>
</form>

<form method="POST" action={{ route('pin.reset', ['user' => $userId]) }}>
    @csrf
    @method('PUT')
    <p>If the activation link has expired, please reset the activation process by clicking on the button below. You will receive a new email to activate your account.</p>
    <button type="submit">Reset the activation process</button>
</form>