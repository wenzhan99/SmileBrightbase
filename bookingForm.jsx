// === helpers ===
const todayISO = new Date().toISOString().split("T")[0]; // yyyy-mm-dd

const initialState = {
  firstName: "",
  lastName: "",
  email: "",
  phone: "",
  date: "",       // yyyy-mm-dd from <input type="date">
  time: "",
  clinic: "",
  service: "",
  experience: "", // New field for experience
  message: "",
  consent: false,
};

const validators = {
  // Name validation: only alphabet characters and spaces
  name: (v) => /^[a-zA-Z\s]+$/.test(v.trim()) && v.trim().length > 0,
  
  // Enhanced email validation: username@domain with specific requirements
  email: (v) => {
    // Username part: word characters including hyphen and period
    // Domain part: two to four extensions, last extension 2-3 characters
    const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,3}$/;
    if (!emailRegex.test(v)) return false;
    
    const [username, domain] = v.split('@');
    const domainParts = domain.split('.');
    
    // Check domain has 2-4 extensions
    if (domainParts.length < 2 || domainParts.length > 4) return false;
    
    // Check last extension has 2-3 characters
    const lastExtension = domainParts[domainParts.length - 1];
    if (lastExtension.length < 2 || lastExtension.length > 3) return false;
    
    return true;
  },
  
  phone: (v) => /^\+?\d{8,15}$/.test(v),
  
  // Date validation: cannot be from today or past (must be future)
  date: (v) => {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) return false;
    const d = new Date(v + "T00:00:00");
    if (Number.isNaN(d.getTime())) return false;
    const today = new Date(); 
    today.setHours(0,0,0,0);
    return d > today; // only allow future dates, not today
  },
  
  // strict 24-hour HH:MM
  time: (v) => /^\d{2}:[0-5]\d$/.test(v.trim()),
  
  // Experience field cannot be empty
  experience: (v) => v.trim().length > 0,
};

function FieldError({ children }) {
  if (!children) return null;
  return <div className="err show" style={{ display: "block" }}>{children}</div>;
}

function BookingForm() {
  const [form, setForm] = React.useState(initialState);
  const [errors, setErrors] = React.useState({});
  const [submitting, setSubmitting] = React.useState(false);
  const [serverMsg, setServerMsg] = React.useState("");

  // 24h times every 15 minutes
  const times = React.useMemo(() => {
    const out = [];
    for (let h = 0; h < 24; h++) {
      for (let m = 0; m < 60; m += 15) {
        out.push(`${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`);
      }
    }
    return out;
  }, []);

  const onChange = (e) => {
    const { name, value, type, checked } = e.target;
    setForm((f) => ({ ...f, [name]: type === "checkbox" ? checked : value }));
  };

  const validate = () => {
    const e = {};
    
    // Name validation: only alphabet characters and spaces
    if (!validators.name(form.firstName)) e.firstName = "First name must contain only alphabet characters and spaces.";
    if (!validators.name(form.lastName)) e.lastName = "Last name must contain only alphabet characters and spaces.";
    
    // Enhanced email validation
    if (!validators.email(form.email)) e.email = "Enter a valid email. Username can contain letters, numbers, hyphens, and periods. Domain must have 2-4 extensions with the last one being 2-3 characters.";
    
    if (!validators.phone(form.phone)) e.phone = "Enter digits only (8–15), optional '+'.";
    
    // Date validation: cannot be today or past
    if (!validators.date(form.date)) e.date = "Pick a future date (not today or past).";
    
    if (!validators.time(form.time)) e.time = "Enter a valid 24-hour time (e.g., 18:30).";
    if (!form.clinic) e.clinic = "Please choose a clinic.";
    if (!form.service) e.service = "Please choose a service.";
    
    // Experience field validation
    if (!validators.experience(form.experience)) e.experience = "Experience field cannot be empty.";
    
    if (!form.consent) e.consent = "You must agree before submitting.";
    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const onSubmit = async (e) => {
    e.preventDefault();
    setServerMsg("");
    if (!validate()) return;

    try {
      setSubmitting(true);
      const payload = new URLSearchParams();
      Object.entries(form).forEach(([k, v]) =>
        payload.append(k, typeof v === "boolean" ? (v ? "1" : "0") : v)
      );

      const res = await fetch("showpost.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: payload.toString(),
      });

      const text = await res.text();
      setServerMsg(text || "Submitted successfully.");
      setForm(initialState);
      setErrors({});
    } catch {
      setServerMsg("Sorry, something went wrong. Please try again.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={onSubmit} noValidate>
      <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 18 }}>
        <div>
          <label htmlFor="firstName">First Name</label>
          <input
            id="firstName"
            name="firstName"
            type="text"
            placeholder="First Name"
            value={form.firstName}
            onChange={onChange}
          />
          <FieldError>{errors.firstName}</FieldError>
        </div>

        <div>
          <label htmlFor="lastName">Last Name</label>
          <input
            id="lastName"
            name="lastName"
            type="text"
            placeholder="Last Name"
            value={form.lastName}
            onChange={onChange}
          />
          <FieldError>{errors.lastName}</FieldError>
        </div>

        <div style={{ gridColumn: "1 / -1" }}>
          <label htmlFor="email">Email Address</label>
          <input
            id="email"
            name="email"
            type="email"
            placeholder="example@mail.com"
            value={form.email}
            onChange={onChange}
          />
          <FieldError>{errors.email}</FieldError>
        </div>

        <div style={{ gridColumn: "1 / -1" }}>
          <label htmlFor="phone">Contact Number</label>
          <input
            id="phone"
            name="phone"
            type="text"
            inputMode="numeric"
            placeholder="12345678"
            value={form.phone}
            onChange={onChange}
          />
          <small className="hint">
            Ensure your number is correct so we can confirm or reschedule if needed.
          </small>
          <FieldError>{errors.phone}</FieldError>
        </div>

        <div>
          <label htmlFor="date">Preferred Date</label>
          <input
            id="date"
            name="date"
            type="date"
            value={form.date}
            onChange={onChange}
            min={todayISO}
            required
          />
          <FieldError>{errors.date}</FieldError>
        </div>

        <div>
          <label htmlFor="time">Time (24-hour)</label>
          <select id="time" name="time" value={form.time} onChange={onChange}>
            <option value="">Select time</option>
            {times.map(t => <option key={t} value={t}>{t}</option>)}
          </select>
          <FieldError>{errors.time}</FieldError>
        </div>

        <div style={{ gridColumn: "1 / -1" }}>
          <label htmlFor="clinic">Preferred Clinic</label>
          <select id="clinic" name="clinic" value={form.clinic} onChange={onChange}>
            <option value="">Select a Preferred Clinic</option>
            <option>Novena</option>
            <option>Tampines</option>
            <option>Jurong East</option>
            <option>Woodlands</option>
            <option>Punggol</option>
          </select>
          <FieldError>{errors.clinic}</FieldError>
        </div>

        <div style={{ gridColumn: "1 / -1" }}>
          <label htmlFor="service">Preferred Services</label>
          <select id="service" name="service" value={form.service} onChange={onChange}>
            <option value="">Select a Preferred Service</option>
            <option>Scaling &amp; Polishing</option>
            <option>Dental Filling</option>
            <option>Teeth Whitening</option>
            <option>Braces / Invisalign</option>
            <option>Wisdom Tooth Removal</option>
          </select>
          <FieldError>{errors.service}</FieldError>
        </div>

        <div style={{ gridColumn: "1 / -1" }}>
          <label htmlFor="experience">Experience</label>
          <textarea
            id="experience"
            name="experience"
            rows="3"
            placeholder="Please describe your dental experience or any previous treatments..."
            value={form.experience}
            onChange={onChange}
            required
          />
          <small className="hint">
            This field is required. Please share any relevant dental history or experience.
          </small>
          <FieldError>{errors.experience}</FieldError>
        </div>

        <div style={{ gridColumn: "1 / -1" }}>
          <label htmlFor="message">Message</label>
          <textarea
            id="message"
            name="message"
            rows="4"
            placeholder="Share any notes (e.g., pain area, preferred dentist)"
            value={form.message}
            onChange={onChange}
          />
        </div>

        <div style={{ gridColumn: "1 / -1", display: "flex", gap: 12, alignItems: "center" }}>
          <label style={{ display: "flex", alignItems: "center", gap: 10 }}>
            <input type="checkbox" name="consent" checked={form.consent} onChange={onChange} />
            I agree to send my information to Smile Bright Dental (Singapore) Pte Ltd and accept the
            <a href="privacy.html" style={{ marginLeft: 6 }}>Privacy Policy</a>
            &nbsp;and&nbsp;<a href="terms.html">Terms</a>.
          </label>
        </div>
        <FieldError>{errors.consent}</FieldError>

        <div style={{ gridColumn: "1 / -1" }}>
          <div style={{
            height: 78, border: "1px dashed var(--ring)", borderRadius: 10,
            display: "flex", alignItems: "center", justifyContent: "center", color: "var(--muted)"
          }}>reCAPTCHA placeholder</div>
        </div>

        <div style={{ gridColumn: "1 / -1" }}>
          <button className="submit" type="submit" disabled={submitting} style={{ opacity: submitting ? .7 : 1 }}>
            {submitting ? "SUBMITTING..." : "SUBMIT"}
          </button>
        </div>

        {serverMsg && (
          <div style={{ gridColumn: "1 / -1", marginTop: 10, color: "#1f4f86", fontWeight: 600 }}>
            {serverMsg}
          </div>
        )}
      </div>
    </form>
  );
}

// Mount
ReactDOM.createRoot(document.getElementById("react-booking")).render(<BookingForm />);
