# Production & Manufacturing - Sample Usage Guide

## Overview

This guide walks you through a complete production workflow using the OptimaSphere ERP Production & Manufacturing module.

---

## Table of Contents

1. [Initial Setup](#initial-setup)
2. [Creating a Bill of Materials (BOM)](#creating-a-bill-of-materials-bom)
3. [Creating a Production Order](#creating-a-production-order)
4. [Scheduling Production](#scheduling-production)
5. [Managing Work Centers](#managing-work-centers)
6. [Material Requisition](#material-requisition)
7. [Complete Production Workflow Example](#complete-production-workflow-example)

---

## Initial Setup

### Prerequisites

Before using the Production module, ensure you have:

✅ **Products configured** (Production & Manufacturing → Products)
- Raw materials/components
- Finished goods

✅ **Units of measure** (Product Management → Units)
- Each product must have a unit (pcs, kg, m, etc.)

✅ **Warehouses** (Warehouse & Inventory → Warehouses)
- At least one warehouse for production

✅ **Work Centers** (Production & Manufacturing → Work Centers)
- Run the seeder: `php artisan db:seed --class=WorkCenterSeeder`
- Or create manually

✅ **User Role** (Manufacturing role or Supervisor status)
- Access to Production & Manufacturing menu

---

## Creating a Bill of Materials (BOM)

### Scenario: Create BOM for "Assembled Widget"

**Step 1: Navigate to BOMs**
```
Production & Manufacturing → Bill of Materials → Create
```

**Step 2: Fill in BOM Details**

**Basic Information:**
- **Name**: `Widget Assembly BOM v1.0`
- **Product**: Select "Assembled Widget" (the finished product)
- **Version**: `1.0` (auto-filled)
- **BOM Type**: `Manufacturing`
- **Description**: `Standard assembly BOM for Widget Model A`

**Quantity & Cost:**
- **Output Quantity**: `1` (this BOM produces 1 unit)
- **Unit**: `pcs`
- **Labor Cost**: `25.00` ($/unit)
- **Overhead Cost**: `15.00` ($/unit)
- **Estimated Production Time**: `120` minutes

**Step 3: Add BOM Items (Components)**

Click "Add Component/Material" and add each required item:

**Component 1: Metal Frame**
- **Product**: Metal Frame
- **Quantity**: `1`
- **Unit**: pcs
- **Unit Cost**: `10.00` (auto-filled from product cost)
- **Scrap %**: `2` (2% waste expected)
- **Sequence**: `1`
- **Type**: Component
- **Optional**: No

**Component 2: Electronic Board**
- **Product**: Electronic Circuit Board
- **Quantity**: `1`
- **Unit**: pcs
- **Unit Cost**: `45.00`
- **Scrap %**: `1`
- **Sequence**: `2`
- **Type**: Component
- **Optional**: No

**Component 3: Fasteners**
- **Product**: M4 Screws Pack
- **Quantity**: `1`
- **Unit**: pack
- **Unit Cost**: `2.50`
- **Scrap %**: `5`
- **Sequence**: `3`
- **Type**: Raw Material
- **Optional**: No

**Component 4: Packaging**
- **Product**: Retail Box
- **Quantity**: `1`
- **Unit**: pcs
- **Unit Cost**: `3.00`
- **Scrap %**: `0`
- **Sequence**: `4`
- **Type**: Component
- **Optional**: No

**Step 4: Review Costs**

The system automatically calculates:
- **Material Cost**: $60.50 (sum of all component costs)
- **Total BOM Cost**: $100.50 (Material + Labor + Overhead)

**Step 5: Save as Draft**

Click **Create** - BOM is saved with status: `Draft`

**Step 6: Submit for Approval**

From the BOM list:
1. Find your BOM
2. Click Actions → **Submit for Approval**
3. Status changes to: `Pending Approval`

**Step 7: Approve BOM**

As a supervisor or manager:
1. Click Actions → **Approve**
2. Status changes to: `Approved`
3. BOM is now available for production orders

---

## Creating a Production Order

### Scenario: Produce 100 Assembled Widgets

**Step 1: Navigate to Production Orders**
```
Production & Manufacturing → Production Orders → Create
```

**Step 2: Fill in Order Details**

**Production Order Details:**
- **Bill of Material**: Select "Widget Assembly BOM v1.0"
- **Product**: Auto-filled (Assembled Widget)
- **Warehouse**: Select "Main Warehouse"
- **Quantity to Produce**: `100`
- **Unit**: Auto-filled (pcs)
- **Priority**: `High`
- **Material Allocation**: `Automatic`

**Schedule:**
- **Planned Start Date**: `2025-10-30`
- **Planned End Date**: `2025-11-05`

**Sales Order Link** (Optional):
- **Sales Order**: Select if linked to customer order
- **Customer Reference**: Customer PO number

**Assignment & Notes:**
- **Assigned To**: Select production supervisor
- **Production Notes**: "Rush order for customer XYZ"

**Step 3: Create Order**

Click **Create**

The system automatically:
- Generates reference: `PRO-20251029-0001`
- Sets product_id from BOM
- Calculates estimated cost: $10,050 (100 × $100.50)
- Calculates estimated time: 12,000 minutes (100 × 120 min)
- Status: `Draft`
- Creates production order items from BOM components

**Step 4: Release Order**

From the Production Orders list:
1. Find your order
2. Click Actions → **Release**
3. If material allocation is "Auto", materials are automatically reserved
4. Status changes to: `Released` or `Materials Reserved`

**Step 5: Start Production**

When ready to begin:
1. Click Actions → **Start Production**
2. Status changes to: `In Progress`
3. Actual start time is recorded

**Step 6: Complete Production**

When finished:
1. Click Actions → **Complete**
2. Confirm completion (system will prompt if needed)
3. Status changes to: `Completed`
4. Finished goods are added to warehouse inventory
5. Stock movements are created

---

## Scheduling Production

### Scenario: Schedule Widget Production on Assembly Line

**Step 1: Navigate to Production Schedules**
```
Production & Manufacturing → Production Schedules → Create
```

**Step 2: Fill in Schedule Details**

**Schedule Information:**
- **Production Order**: Select "PRO-20251029-0001"
- **Work Center**: Select "Assembly Line A"
- **Operation Name**: `Widget Assembly`
- **Sequence**: `1` (first operation)
- **Priority**: `High`

**Schedule Times:**
- **Scheduled Start**: `2025-10-30 08:00`
- **Scheduled End**: `2025-10-30 16:00`
- **Setup Time**: `60` minutes
- **Run Time**: `400` minutes
- **Teardown Time**: `30` minutes
- **Total Duration**: `490` minutes (auto-calculated)

**Quantity & Assignment:**
- **Quantity Scheduled**: `100`
- **Assigned Operator**: Select operator

**Step 3: Create Schedule**

Click **Create**

The system automatically:
- Generates reference: `SCH-20251029-0001`
- Detects conflicts with other schedules on same work center
- Shows warning if conflicts exist

**Conflict Detection:**

If another schedule exists for "Assembly Line A" at the same time:
- ⚠️ **Has Conflict**: Yes
- **Conflict Details**: "Conflicts with schedule SCH-20251029-0002 (2025-10-30 07:00 - 2025-10-30 17:00)"
- Badge shows conflict count in red

**Step 4: Start Operation**

When ready:
1. Click Actions → **Start**
2. Status changes to: `In Progress`
3. Actual start time recorded

**Step 5: Complete Operation**

When finished:
1. Click Actions → **Complete**
2. Enter completion data:
   - **Quantity Completed**: `98`
   - **Quantity Scrapped**: `2`
   - **Completion Notes**: "Minor defects in 2 units"
3. Status changes to: `Completed`
4. Production order quantities updated

---

## Managing Work Centers

### View Work Centers

**Navigate:**
```
Production & Manufacturing → Work Centers
```

**Available Work Centers** (from seeder):
- CNC Machining Center 1 (16 hrs/day, $75/hr)
- CNC Machining Center 2 (16 hrs/day, $125/hr)
- Welding Station 1 (8 hrs/day, $45/hr)
- Assembly Line A (200 units/day, $30/hr)
- Quality Control Station (100 units/day, $40/hr)
- Laser Cutting Machine (14 hrs/day, $95/hr)
- Paint Booth 1 (50 units/day, $55/hr)
- Packaging Line 1 (500 units/day, $35/hr)

### Create New Work Center

**Step 1: Create Work Center**
```
Production & Manufacturing → Work Centers → Create
```

**Example: CNC Lathe**

**Work Center Information:**
- **Code**: Auto-generated (WC0009)
- **Name**: `CNC Lathe Machine`
- **Type**: `Machine`
- **Warehouse Location**: Main Warehouse
- **Location Details**: `Floor 1, Bay B, Line 3`
- **Description**: `Precision turning for cylindrical parts`

**Capacity & Performance:**
- **Capacity per Day**: `14`
- **Capacity Unit**: `Hours`
- **Efficiency %**: `85`
- **Cost per Hour**: `$65.00`

**Time Settings:**
- **Setup Time**: `25` minutes
- **Teardown Time**: `15` minutes
- **Minimum Batch Size**: `1`
- **Maximum Batch Size**: `200`

**Operator Requirements:**
- **Requires Operator**: Yes
- **Number of Operators**: `1`
- **Supervisor**: Select supervisor

**Capabilities & Certifications:**
- **Capabilities**: `turning`, `threading`, `boring`, `facing`
- **Certifications**: `ISO 9001`, `CE`

**Maintenance:**
- **Next Maintenance Due**: `2025-12-01`
- **Maintenance Notes**: `Quarterly preventive maintenance`

**Status:**
- **Active**: Yes
- **Available**: Yes

---

## Material Requisition

### Scenario: Request Materials for Production Order

**Step 1: Navigate to Material Requisitions**
```
Production & Manufacturing → Material Requisitions → Create
```

**Step 2: Create Requisition**

**Requisition Information:**
- **Production Order**: Select "PRO-20251029-0001"
- **Warehouse**: Main Warehouse
- **Type**: `Manual`
- **Priority**: `High`
- **Required Date**: `2025-10-30`
- **Notes**: "Materials needed for Widget production run"

**Step 3: Review Auto-Generated Items**

System automatically creates requisition items from production order:
- Metal Frame: 100 pcs
- Electronic Circuit Board: 100 pcs
- M4 Screws Pack: 100 packs
- Retail Box: 100 pcs

**Step 4: Submit Requisition**

Click **Create** - Requisition saved with status: `Draft`

**Note:** Full material picking workflow requires additional model logic (see implementation docs).

---

## Complete Production Workflow Example

### End-to-End: Manufacturing 50 Custom Widgets

#### Day 1: Planning

**09:00 - Create BOM**
1. Create "Custom Widget BOM v1.0"
2. Add 6 components
3. Set labor cost: $30, overhead: $20
4. Submit for approval
5. Manager approves BOM

**10:00 - Create Production Order**
1. Create order PRO-20251029-0002
2. Select Custom Widget BOM
3. Quantity: 50 units
4. Link to Sales Order SO-2025-1234
5. Planned dates: Oct 30 - Nov 3
6. Assign to Production Supervisor
7. Release order
8. Materials automatically reserved

#### Day 2: Scheduling

**08:00 - Schedule Operations**

**Operation 1: Cutting**
- Work Center: Laser Cutting Machine
- Schedule: Oct 30, 08:00-10:00
- Quantity: 50

**Operation 2: Machining**
- Work Center: CNC Machining Center 1
- Schedule: Oct 30, 10:30-14:00
- Quantity: 50

**Operation 3: Welding**
- Work Center: Welding Station 1
- Schedule: Oct 31, 08:00-12:00
- Quantity: 50

**Operation 4: Assembly**
- Work Center: Assembly Line A
- Schedule: Nov 1, 08:00-16:00
- Quantity: 50

**Operation 5: Quality Control**
- Work Center: Quality Control Station
- Schedule: Nov 2, 08:00-11:00
- Quantity: 50

**Operation 6: Packaging**
- Work Center: Packaging Line 1
- Schedule: Nov 2, 13:00-14:00
- Quantity: 50

**Conflict Check:** No conflicts detected ✓

#### Day 3-7: Execution

**Oct 30 - Cutting & Machining**
- Operator starts SCH-001 (Cutting)
- Completes: 50 units, 0 scrap
- Operator starts SCH-002 (Machining)
- Completes: 49 units, 1 scrap

**Oct 31 - Welding**
- Operator starts SCH-003 (Welding)
- Completes: 49 units, 0 scrap

**Nov 1 - Assembly**
- Team starts SCH-004 (Assembly)
- Completes: 48 units, 1 scrap

**Nov 2 - QC & Packaging**
- QC Inspector starts SCH-005 (Quality Control)
- Completes: 47 units, 1 failed inspection
- Team starts SCH-006 (Packaging)
- Completes: 47 units, 0 scrap

**Nov 2 - Complete Production Order**
- Click Complete on PRO-20251029-0002
- Final quantity: 47 units (3 scrapped)
- Finished goods added to Main Warehouse
- Stock movement created
- Customer notified

---

## Key Features Demonstrated

### ✅ Bill of Materials (BOM)
- Multi-level component structures
- Version control
- Approval workflow
- Cost roll-up calculations
- Scrap percentage handling

### ✅ Production Orders
- BOM-based order generation
- Automatic cost and time estimation
- Material reservation
- Sales order linking
- Progress tracking
- Inventory integration

### ✅ Production Scheduling
- Work center assignment
- Conflict detection
- Operation sequencing
- Time tracking (setup, run, teardown)
- Quantity and scrap tracking

### ✅ Work Centers
- Multiple types (machine, manual, assembly, QC, packaging)
- Capacity management
- Utilization tracking
- Cost per hour
- Maintenance scheduling
- Operator requirements

### ✅ Material Requisitions
- Production order integration
- Warehouse selection
- Priority management
- Automatic item generation

---

## Best Practices

### BOM Management
1. ✅ Always version your BOMs (v1.0, v1.1, v2.0)
2. ✅ Include scrap percentages based on historical data
3. ✅ Keep labor and overhead costs updated
4. ✅ Use approval workflow for production BOMs
5. ✅ Set effective and expiry dates for time-limited BOMs

### Production Orders
1. ✅ Link to sales orders for traceability
2. ✅ Use priority levels (urgent for rush orders)
3. ✅ Set realistic planned dates
4. ✅ Assign to specific supervisors
5. ✅ Use automatic material allocation for standard runs
6. ✅ Use manual allocation for custom/complex orders

### Scheduling
1. ✅ Schedule in operation sequence order
2. ✅ Include setup and teardown times
3. ✅ Check for conflicts before confirming
4. ✅ Allow buffer time between operations
5. ✅ Assign operators to schedules in advance
6. ✅ Monitor overdue schedules daily

### Work Centers
1. ✅ Set realistic capacity based on historical data
2. ✅ Update efficiency % quarterly
3. ✅ Track maintenance schedules proactively
4. ✅ Mark centers unavailable during maintenance
5. ✅ Review utilization weekly for bottlenecks
6. ✅ Document capabilities for accurate scheduling

---

## Reports & Analytics

### Available Reports (via models)

**Production Order Reports:**
- Active orders: `ProductionOrder::active()->get()`
- Overdue orders: `ProductionOrder::overdue()->get()`
- By status: `ProductionOrder::status('in_progress')->get()`
- Cost variance: Compare estimated_cost vs actual_cost

**Work Center Performance:**
- Utilization: Check `utilization_percentage` field
- OEE metrics: `WorkCenterPerformanceLog` model
- Maintenance due: `WorkCenter::maintenanceDue()->get()`

**Schedule Analysis:**
- Conflicts: `ProductionSchedule::withConflicts()->get()`
- Overdue: `ProductionSchedule::overdue()->get()`
- By work center: `ProductionSchedule::forWorkCenter($id)->get()`

---

## Troubleshooting

### Common Issues

**Issue: BOM won't approve**
- Check all items have valid products
- Ensure all costs are set
- Verify BOM status is "Pending Approval"

**Issue: Production order creation fails**
- Verify BOM is approved
- Check all required fields
- Ensure product_id is set (auto-filled from BOM)

**Issue: Schedule conflicts**
- Review work center availability
- Check overlapping time slots
- Adjust start/end times or use different work center

**Issue: Materials not reserved**
- Check material_allocation_mode is "auto"
- Verify sufficient stock in warehouse
- Ensure ProductWarehouseStock records exist

**Issue: Division by zero on completion**
- Always enter quantity_produced > 0
- System now prevents division by zero

---

## Advanced Features

### Multi-Level BOMs
Create BOMs that reference other BOMs:
1. Create BOM for sub-assembly
2. Approve sub-assembly BOM
3. Create parent BOM
4. Add sub-assembly as a component
5. System handles recursive cost calculations

### Batch/Lot Tracking
Track materials by batch:
- Link materials to ProductBatch records
- Record batch numbers in material picks
- Trace materials through production
- Support FIFO/FEFO selection

### Backflushing
Automatic material consumption:
- Set consumption_type: 'backflush'
- Materials consumed on operation completion
- Based on BOM quantities
- Reduces manual tracking

---

## Quick Reference

### Navigation Paths
```
Production & Manufacturing
├── Bill of Materials          (Sort: 10, Badge: Pending Approvals)
├── Production Orders          (Sort: 20, Badge: Active Orders)
├── Work Centers              (Sort: 30, Badge: Active Centers)
├── Production Schedules      (Sort: 40, Badge: Conflicts)
├── Material Requisitions     (Sort: 50, Badge: Submitted)
├── Production Order Operations (WIP Tracking)
└── Work Center Maintenances   (Maintenance Scheduling)
```

### Reference Code Formats
- BOM: `BOM-YYYYMMDD-####`
- Production Order: `PRO-YYYYMMDD-####`
- Schedule: `SCH-YYYYMMDD-####`
- Work Center: `WC####`
- Material Requisition: `MR-YYYYMMDD-####`
- Material Consumption: `MC-YYYYMMDD-####`
- Maintenance: `MNT-YYYYMMDD-####`

### Status Workflows

**BOM:**
draft → pending_approval → approved/rejected/obsolete

**Production Order:**
draft → planned → released → materials_reserved → in_progress → completed/cancelled

**Schedule:**
scheduled → ready → in_progress → completed/cancelled/on_hold

**Material Requisition:**
draft → submitted → approved → picking → issued → completed/cancelled

---

## Next Steps

1. **Create your first BOM** following the example above
2. **Generate a production order** from your BOM
3. **Schedule operations** on work centers
4. **Execute production** and track progress
5. **Review performance** using work center metrics

For technical details, see:
- `PRODUCTION_MODULE_IMPLEMENTATION.md`
- `WIP_AND_MAINTENANCE_IMPLEMENTATION.md`
- `MATERIAL_MANAGEMENT_IMPLEMENTATION.md`

---

**Version:** 1.0.0
**Last Updated:** 2025-10-29
**Author:** Webtech-Solutions
**Project:** OptimaSphere ERP
